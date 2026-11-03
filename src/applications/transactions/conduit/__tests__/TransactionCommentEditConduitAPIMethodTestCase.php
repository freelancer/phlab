<?php

/**
 * `transaction.comment.edit` against real storage, on tasks and revisions,
 * with the comment's author and another user.
 */
final class TransactionCommentEditConduitAPIMethodTestCase
  extends PhabricatorTestCase {

  const DIFF = <<<'EODIFF'
diff --git a/src/Example.php b/src/Example.php
--- a/src/Example.php
+++ b/src/Example.php
@@ -1,3 +1,3 @@
 <?php
-$x = 1;
+$x = 2;
 return $x;
EODIFF;

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testTheMethodLoads(): void {
    $methods = ConduitAPIMethod::loadAllConduitMethods();

    $this->assertTrue(isset($methods['transaction.comment.edit']));
    $this->assertTrue(
      (bool)$methods['transaction.comment.edit']->getParamTypes());
  }

  public function testEditsATaskCommentAsANewVersion(): void {
    list($author) = $this->newUsers(1);
    $task = $this->newTask($author);
    $xaction_phid = $this->addTaskComment($author, $task, 'Frist.');

    $result = $this->call($author, 'transaction.comment.edit', [
      'objectIdentifier' => 'T'.$task['id'],
      'transactionPHID' => $xaction_phid,
      'content' => 'First.',
    ]);

    $this->assertEqual($task['phid'], $result['objectPHID']);
    $this->assertEqual($xaction_phid, $result['transactionPHID']);
    $this->assertEqual(2, $result['comment']['version']);
    $this->assertEqual('First.', $result['comment']['content']['raw']);
    $this->assertEqual($author->getPHID(), $result['comment']['authorPHID']);
    $this->assertFalse($result['comment']['removed']);

    // "transaction.search" shows the new version first, and keeps the old
    // one as history, as after an edit in the web UI.
    $comments = $this->searchComments($author, $task['phid'], $xaction_phid);
    $this->assertEqual([2, 1], ipull($comments, 'version'));
    $this->assertEqual(
      ['First.', 'Frist.'],
      ipull(ipull($comments, 'content'), 'raw'));
    $this->assertEqual($result['comment'], head($comments));
  }

  public function testEditsARevisionCommentByPHID(): void {
    list($author, $reviewer) = $this->newUsers(2);
    $revision_phid = $this->newRevision($author, [$reviewer]);

    $this->call($reviewer, 'differential.revision.edit', [
      'objectIdentifier' => $revision_phid,
      'transactions' => [
        ['type' => 'comment', 'value' => 'Lookss good.'],
      ],
    ]);
    $xaction_phid = $this->findCommentPHID($reviewer, $revision_phid);

    $result = $this->call($reviewer, 'transaction.comment.edit', [
      'objectIdentifier' => $revision_phid,
      'transactionPHID' => $xaction_phid,
      'content' => 'Looks good.',
    ]);

    $this->assertEqual(2, $result['comment']['version']);
    $this->assertEqual(
      'Looks good.',
      head($this->searchComments($reviewer, $revision_phid, $xaction_phid))
        ['content']['raw']);
  }

  public function testEditingTwiceKeepsCounting(): void {
    list($author) = $this->newUsers(1);
    $task = $this->newTask($author);
    $xaction_phid = $this->addTaskComment($author, $task, 'One.');

    foreach (['Two.', 'Three.'] as $content) {
      $result = $this->call($author, 'transaction.comment.edit', [
        'objectIdentifier' => $task['phid'],
        'transactionPHID' => $xaction_phid,
        'content' => $content,
      ]);
    }

    $this->assertEqual(3, $result['comment']['version']);
    $this->assertEqual('Three.', $result['comment']['content']['raw']);
  }

  public function testOnlyTheAuthorCanEdit(): void {
    list($author, $other) = $this->newUsers(2);
    $task = $this->newTask($author);
    $xaction_phid = $this->addTaskComment($author, $task, 'Mine.');

    $this->assertConduitError('ERR-NOT-EDITABLE', $other, [
      'objectIdentifier' => $task['phid'],
      'transactionPHID' => $xaction_phid,
      'content' => 'Not yours.',
    ]);

    $this->assertEqual(
      [1],
      ipull(
        $this->searchComments($author, $task['phid'], $xaction_phid),
        'version'));
  }

  public function testOnlyRegularComments(): void {
    list($author) = $this->newUsers(1);
    $task = $this->newTask($author);

    // The title change from creating the task has no comment.
    $title_phid = $this->findTransactionPHID(
      $author,
      $task['phid'],
      'title');

    $this->assertConduitError('ERR-NOT-EDITABLE', $author, [
      'objectIdentifier' => $task['phid'],
      'transactionPHID' => $title_phid,
      'content' => 'Text.',
    ], 'a title change');
  }

  public function testInlineCommentsAreRefused(): void {
    list($author, $reviewer) = $this->newUsers(2);
    $revision_phid = $this->newRevision($author, [$reviewer]);
    $revision_id = $this->loadRevisionID($author, $revision_phid);

    $this->call($reviewer, 'differential.inline.create', [
      'revisionID' => $revision_id,
      'filePath' => 'src/Example.php',
      'isNewFile' => true,
      'lineNumber' => 2,
      'content' => 'Why 2?',
    ]);
    $this->call($reviewer, 'differential.inline.publish', [
      'revisionID' => $revision_id,
    ]);

    $inline_phid = $this->findTransactionPHID(
      $reviewer,
      $revision_phid,
      'inline');

    $this->assertConduitError('ERR-NOT-EDITABLE', $reviewer, [
      'objectIdentifier' => $revision_phid,
      'transactionPHID' => $inline_phid,
      'content' => 'Why 3?',
    ], 'a published inline');
  }

  public function testRemovedCommentsAreRefused(): void {
    list($author) = $this->newUsers(1);
    $task = $this->newTask($author);
    $xaction_phid = $this->addTaskComment($author, $task, 'Spam.');

    // As an administrator's "Remove Comment" leaves it.
    $xaction = $this->loadTaskTransaction($author, $xaction_phid);
    $xaction->getComment()->setIsRemoved(true)->save();

    $this->assertConduitError('ERR-NOT-EDITABLE', $author, [
      'objectIdentifier' => $task['phid'],
      'transactionPHID' => $xaction_phid,
      'content' => 'Back again.',
    ]);
  }

  public function testCommentsSignedWithMFAAreRefused(): void {
    list($author) = $this->newUsers(1);
    $task = $this->newTask($author);
    $xaction_phid = $this->addTaskComment($author, $task, 'Signed.');

    $xaction = $this->loadTaskTransaction($author, $xaction_phid);
    $xaction->setIsMFATransaction(true)->save();

    $this->assertConduitError('ERR-MFA', $author, [
      'objectIdentifier' => $task['phid'],
      'transactionPHID' => $xaction_phid,
      'content' => 'Unsigned.',
    ]);
  }

  public function testLockedConversationsAreRefused(): void {
    list($author) = $this->newUsers(1);
    $task = $this->newTask($author);
    $xaction_phid = $this->addTaskComment($author, $task, 'Before.');

    $statuses = PhabricatorEnv::getEnvConfig('maniphest.statuses');
    $statuses['phlablocked'] = [
      'name' => 'Locked',
      'closed' => false,
      'locked' => true,
    ];
    $env = PhabricatorEnv::beginScopedEnv();
    $env->overrideEnvConfig('maniphest.statuses', $statuses);

    $task_object = id(new ManiphestTaskQuery())
      ->setViewer($author)
      ->withPHIDs([$task['phid']])
      ->executeOne();
    $task_object->setStatus('phlablocked')->save();

    $this->assertConduitError('ERR-LOCKED', $author, [
      'objectIdentifier' => $task['phid'],
      'transactionPHID' => $xaction_phid,
      'content' => 'After.',
    ]);

    unset($env);
  }

  public function testUnknownObjectsAndTransactions(): void {
    list($author) = $this->newUsers(1);
    $task = $this->newTask($author);
    $xaction_phid = $this->addTaskComment($author, $task, 'Hi.');
    $other_task = $this->newTask($author);

    $this->assertConduitError('ERR-BAD-OBJECT', $author, [
      'objectIdentifier' => 'T999999999',
      'transactionPHID' => $xaction_phid,
      'content' => 'Text.',
    ], 'no such task');

    $this->assertConduitError('ERR-BAD-TRANSACTION', $author, [
      'objectIdentifier' => $other_task['phid'],
      'transactionPHID' => $xaction_phid,
      'content' => 'Text.',
    ], 'a comment on another task');

    $this->assertConduitError('ERR-BAD-TRANSACTION', $author, [
      'objectIdentifier' => $task['phid'],
      'transactionPHID' => 'PHID-XACT-TASK-doesnotexist',
      'content' => 'Text.',
    ], 'no such transaction');
  }

  public function testEmptyContentIsRefused(): void {
    list($author) = $this->newUsers(1);
    $task = $this->newTask($author);
    $xaction_phid = $this->addTaskComment($author, $task, 'Keep me.');

    $this->assertConduitError('ERR-EMPTY-COMMENT', $author, [
      'objectIdentifier' => $task['phid'],
      'transactionPHID' => $xaction_phid,
      'content' => " \n",
    ]);

    $caught = null;
    try {
      $this->call($author, 'transaction.comment.edit', [
        'objectIdentifier' => $task['phid'],
        'transactionPHID' => $xaction_phid,
      ]);
    } catch (Throwable $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught !== null, 'missing "content"');
    $this->assertEqual(
      'Parameter "content" is required.',
      $caught->getMessage(),
      'missing "content"');
  }

  private function newUsers(int $count): array {
    $users = [];
    for ($ii = 0; $ii < $count; $ii++) {
      $users[] = $this->generateNewTestUser();
    }
    return $users;
  }

  private function newTask(PhabricatorUser $author): array {
    $result = $this->call($author, 'maniphest.edit', [
      'transactions' => [
        ['type' => 'title', 'value' => 'Comment edit fixture'],
      ],
    ]);

    return $result['object'];
  }

  private function addTaskComment(
    PhabricatorUser $author,
    array $task,
    string $content): string {

    $this->call($author, 'maniphest.edit', [
      'objectIdentifier' => $task['phid'],
      'transactions' => [
        ['type' => 'comment', 'value' => $content],
      ],
    ]);

    return $this->findCommentPHID($author, $task['phid']);
  }

  private function newRevision(
    PhabricatorUser $author,
    array $reviewers): string {

    $changes = id(new ArcanistDiffParser())->parseDiff(self::DIFF);
    $diff = DifferentialDiff::newFromRawChanges($author, $changes)
      ->setLintStatus(DifferentialLintStatus::LINT_AUTO_SKIP)
      ->setUnitStatus(DifferentialUnitStatus::UNIT_AUTO_SKIP)
      ->attachRevision(null)
      ->save();

    $result = $this->call($author, 'differential.revision.edit', [
      'transactions' => [
        ['type' => 'update', 'value' => $diff->getPHID()],
        ['type' => 'title', 'value' => 'Comment edit fixture'],
        ['type' => 'testPlan', 'value' => 'Fixture.'],
        [
          'type' => 'reviewers.add',
          'value' => mpull($reviewers, 'getPHID'),
        ],
      ],
    ]);

    return $result['object']['phid'];
  }

  private function loadRevisionID(
    PhabricatorUser $viewer,
    string $revision_phid): int {

    return (int)id(new DifferentialRevisionQuery())
      ->setViewer($viewer)
      ->withPHIDs([$revision_phid])
      ->executeOne()
      ->getID();
  }

  private function loadTaskTransaction(
    PhabricatorUser $viewer,
    string $xaction_phid): ManiphestTransaction {

    return id(new ManiphestTransactionQuery())
      ->setViewer($viewer)
      ->withPHIDs([$xaction_phid])
      ->executeOne();
  }

  private function searchTransactions(
    PhabricatorUser $viewer,
    string $object_phid): array {

    $result = $this->call($viewer, 'transaction.search', [
      'objectIdentifier' => $object_phid,
    ]);

    return $result['data'];
  }

  private function findTransactionPHID(
    PhabricatorUser $viewer,
    string $object_phid,
    string $type): string {

    foreach ($this->searchTransactions($viewer, $object_phid) as $xaction) {
      if ($xaction['type'] === $type) {
        return $xaction['phid'];
      }
    }

    throw new Exception(
      pht('No "%s" transaction on "%s".', $type, $object_phid));
  }

  private function findCommentPHID(
    PhabricatorUser $viewer,
    string $object_phid): string {

    return $this->findTransactionPHID($viewer, $object_phid, 'comment');
  }

  private function searchComments(
    PhabricatorUser $viewer,
    string $object_phid,
    string $xaction_phid): array {

    foreach ($this->searchTransactions($viewer, $object_phid) as $xaction) {
      if ($xaction['phid'] === $xaction_phid) {
        return $xaction['comments'];
      }
    }

    return [];
  }

  private function call(
    PhabricatorUser $user,
    string $method,
    array $params) {

    return id(new ConduitCall($method, $params, true))
      ->setUser($user)
      ->execute();
  }

  private function assertConduitError(
    string $code,
    PhabricatorUser $user,
    array $params,
    ?string $label = null): void {

    $caught = null;
    try {
      $this->call($user, 'transaction.comment.edit', $params);
    } catch (ConduitException $ex) {
      $caught = $ex;
    }

    $label = nonempty($label, $code);
    $this->assertTrue($caught !== null, pht('%s: expected %s', $label, $code));
    $this->assertEqual($code, $caught->getMessage(), $label);
  }

}

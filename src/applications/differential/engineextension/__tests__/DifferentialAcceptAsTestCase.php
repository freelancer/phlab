<?php

/**
 * `accept.as` in `differential.revision.edit`, against real storage: a
 * revision by an author, reviewed by a bot user and two projects the bot is
 * a member of, and a third project it is not.
 */
final class DifferentialAcceptAsTestCase
  extends PhabricatorTestCase {

  const RAW_DIFF = <<<'EODIFF'
diff --git a/src/Example.php b/src/Example.php
--- a/src/Example.php
+++ b/src/Example.php
@@ -1,3 +1,3 @@
 <?php
-echo 'a';
+echo 'b';
 return;
EODIFF;

  const ACCEPTED = DifferentialReviewerStatus::STATUS_ACCEPTED;
  const ADDED = DifferentialReviewerStatus::STATUS_ADDED;

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testTheTypeIsListedBesideAccept(): void {
    $types = id(new DifferentialRevisionEditEngine())
      ->setViewer($this->generateNewTestUser())
      ->getConduitEditTypes();

    $this->assertTrue(isset($types['accept.as']), 'accept.as');
    $this->assertTrue(isset($types['accept']), 'accept');
    $this->assertEqual(
      'list<phid>',
      $types['accept.as']->getConduitParameterType()->getTypeName());
    $this->assertEqual(
      'bool',
      $types['accept']->getConduitParameterType()->getTypeName());
  }

  public function testSelfOnlyLeavesEveryGroupWaiting(): void {
    $f = $this->newFixture();

    $this->accept($f, [$f['bot']->getPHID()]);

    $this->assertStatuses($f, [
      'bot' => self::ACCEPTED,
      'groupA' => self::ADDED,
      'groupB' => self::ADDED,
    ]);
  }

  public function testOnlyTheChosenGroupsAreAccepted(): void {
    $f = $this->newFixture();

    $this->accept($f, [$f['bot']->getPHID(), $f['groupA']->getPHID()]);

    $this->assertStatuses($f, [
      'bot' => self::ACCEPTED,
      'groupA' => self::ACCEPTED,
      'groupB' => self::ADDED,
    ]);
  }

  public function testAGroupCanBeAcceptedWithoutYourself(): void {
    $f = $this->newFixture();

    $this->accept($f, [$f['groupB']->getPHID()]);

    $this->assertStatuses($f, [
      'bot' => self::ADDED,
      'groupA' => self::ADDED,
      'groupB' => self::ACCEPTED,
    ]);
  }

  public function testAGroupCanBeAcceptedAfterYourself(): void {
    $f = $this->newFixture();

    $this->accept($f, [$f['bot']->getPHID()]);
    $this->accept($f, [$f['groupA']->getPHID()]);

    $this->assertStatuses($f, [
      'bot' => self::ACCEPTED,
      'groupA' => self::ACCEPTED,
      'groupB' => self::ADDED,
    ]);
  }

  public function testAcceptStillAcceptsForEveryGroup(): void {
    $f = $this->newFixture();

    $this->edit($f['bot'], $f['revision'], [
      ['type' => 'accept', 'value' => true],
    ]);

    $this->assertStatuses($f, [
      'bot' => self::ACCEPTED,
      'groupA' => self::ACCEPTED,
      'groupB' => self::ACCEPTED,
    ]);
  }

  public function testTheTransactionKeepsTheList(): void {
    $f = $this->newFixture();

    $result = $this->accept($f, [$f['bot']->getPHID()]);

    $xaction = id(new DifferentialTransactionQuery())
      ->setViewer($f['bot'])
      ->withPHIDs([$result['transactions'][0]['phid']])
      ->executeOne();
    $this->assertEqual(
      DifferentialRevisionAcceptTransaction::TRANSACTIONTYPE,
      $xaction->getTransactionType());
    $this->assertEqual([$f['bot']->getPHID()], $xaction->getNewValue());
    $this->assertEqual(
      true,
      $xaction->getMetadataValue(
        DifferentialAcceptAsEditEngineExtension::METADATA_KEY));
  }

  public function testACommentGoesInTheSameEdit(): void {
    $f = $this->newFixture();

    $this->edit($f['bot'], $f['revision'], [
      ['type' => 'comment', 'value' => 'Looks right.'],
      ['type' => 'accept.as', 'value' => [$f['bot']->getPHID()]],
    ]);

    $this->assertStatuses($f, [
      'bot' => self::ACCEPTED,
      'groupA' => self::ADDED,
      'groupB' => self::ADDED,
    ]);
  }

  public function testReviewersYouCannotActForAreRefused(): void {
    $f = $this->newFixture();
    $bot = $f['bot']->getPHID();

    $refused = [
      'empty list' => [],
      'group the bot is not in' => [$bot, $f['groupC']->getPHID()],
      'the author' => [$f['author']->getPHID()],
      'a user who is not a reviewer' => [
        $this->generateNewTestUser()->getPHID(),
      ],
      'not a PHID' => ['letick'],
    ];

    foreach ($refused as $label => $value) {
      $this->assertValidationError($f, [
        ['type' => 'accept.as', 'value' => $value],
      ], $label);
    }

    $this->assertStatuses($f, [
      'bot' => self::ADDED,
      'groupA' => self::ADDED,
      'groupB' => self::ADDED,
      'groupC' => self::ADDED,
    ]);
  }

  public function testOtherReviewActionsInTheSameEditAreRefused(): void {
    $f = $this->newFixture();
    $bot = [$f['bot']->getPHID()];

    $refused = [
      'with accept' => [
        ['type' => 'accept.as', 'value' => $bot],
        ['type' => 'accept', 'value' => true],
      ],
      'accept first' => [
        ['type' => 'accept', 'value' => true],
        ['type' => 'accept.as', 'value' => $bot],
      ],
      'with reject' => [
        ['type' => 'accept.as', 'value' => $bot],
        ['type' => 'reject', 'value' => true],
      ],
      'twice' => [
        ['type' => 'accept.as', 'value' => $bot],
        ['type' => 'accept.as', 'value' => [$f['groupA']->getPHID()]],
      ],
    ];

    foreach ($refused as $label => $xactions) {
      $this->assertValidationError($f, $xactions, $label);
    }

    $this->assertStatuses($f, [
      'bot' => self::ADDED,
      'groupA' => self::ADDED,
      'groupB' => self::ADDED,
    ]);
  }

  public function testItNeedsWhatAcceptNeeds(): void {
    $f = $this->newFixture(function (PhabricatorUser $author) {
      return [['type' => 'edit', 'value' => $author->getPHID()]];
    });

    foreach (['accept.as' => [$f['bot']->getPHID()], 'accept' => true] as
      $type => $value) {

      $caught = null;
      try {
        $this->edit($f['bot'], $f['revision'], [
          ['type' => $type, 'value' => $value],
        ]);
      } catch (PhabricatorPolicyException $ex) {
        $caught = $ex;
      }
      $this->assertTrue($caught !== null, pht('%s needs Can Edit', $type));
    }

    $this->assertStatuses($f, [
      'bot' => self::ADDED,
      'groupA' => self::ADDED,
      'groupB' => self::ADDED,
    ]);
  }

  public function testPlainHTTPParametersAreParsed(): void {
    $f = $this->newFixture();

    // As a form-encoded call (curl --data-urlencode) arrives.
    $this->call($f['bot'], 'differential.revision.edit', [
      'objectIdentifier' => 'D'.$f['revision']->getID(),
      'transactions' => [
        ['type' => 'accept.as', 'value' => [$f['bot']->getPHID()]],
      ],
    ], false);

    $this->assertStatuses($f, [
      'bot' => self::ACCEPTED,
      'groupA' => self::ADDED,
      'groupB' => self::ADDED,
    ]);
  }

  private function newFixture(?callable $extra_xactions = null): array {
    $author = $this->generateNewTestUser();
    $bot = $this->generateNewTestUser();

    $fixture = [
      'author' => $author,
      'bot' => $bot,
      'groupA' => $this->newProject($author, [$bot]),
      'groupB' => $this->newProject($author, [$bot]),
      'groupC' => $this->newProject($author, []),
    ];

    $changes = id(new ArcanistDiffParser())->parseDiff(self::RAW_DIFF);
    $diff = DifferentialDiff::newFromRawChanges($author, $changes)
      ->setLintStatus(DifferentialLintStatus::LINT_AUTO_SKIP)
      ->setUnitStatus(DifferentialUnitStatus::UNIT_AUTO_SKIP)
      ->attachRevision(null)
      ->save();

    $reviewers = [
      $bot->getPHID(),
      $fixture['groupA']->getPHID(),
      $fixture['groupB']->getPHID(),
      $fixture['groupC']->getPHID(),
    ];

    $result = $this->call($author, 'differential.revision.edit', [
      'transactions' => array_merge([
        ['type' => 'update', 'value' => $diff->getPHID()],
        ['type' => 'title', 'value' => 'Accept as fixture'],
        ['type' => 'testPlan', 'value' => 'Fixture.'],
        ['type' => 'reviewers.add', 'value' => $reviewers],
      ], $extra_xactions ? $extra_xactions($author) : []),
    ]);

    $fixture['revision'] = $this->loadRevision(
      $author,
      $result['object']['phid']);

    if ($fixture['revision']->isDraft()) {
      $this->edit($author, $fixture['revision'], [
        ['type' => 'request-review', 'value' => true],
      ]);
      $fixture['revision'] = $this->loadRevision(
        $author,
        $result['object']['phid']);
    }

    $this->assertFalse($fixture['revision']->isDraft(), 'fixture is public');

    return $fixture;
  }

  private function newProject(PhabricatorUser $creator, array $members) {
    $xactions = [
      [
        'type' => 'name',
        'value' => 'Group '.Filesystem::readRandomCharacters(8),
      ],
    ];
    if ($members) {
      $xactions[] = [
        'type' => 'members.add',
        'value' => mpull($members, 'getPHID'),
      ];
    }

    $result = $this->call($creator, 'project.edit', [
      'transactions' => $xactions,
    ]);

    return id(new PhabricatorProjectQuery())
      ->setViewer($creator)
      ->withPHIDs([$result['object']['phid']])
      ->executeOne();
  }

  private function loadRevision(
    PhabricatorUser $viewer,
    string $phid): DifferentialRevision {

    return id(new DifferentialRevisionQuery())
      ->setViewer($viewer)
      ->withPHIDs([$phid])
      ->needActiveDiffs(true)
      ->needReviewers(true)
      ->executeOne();
  }

  private function accept(array $fixture, array $reviewer_phids): array {
    return $this->edit($fixture['bot'], $fixture['revision'], [
      ['type' => 'accept.as', 'value' => $reviewer_phids],
    ]);
  }

  private function edit(
    PhabricatorUser $user,
    DifferentialRevision $revision,
    array $xactions): array {

    return $this->call($user, 'differential.revision.edit', [
      'objectIdentifier' => 'D'.$revision->getID(),
      'transactions' => $xactions,
    ]);
  }

  private function assertStatuses(array $fixture, array $expect): void {
    $revision = $this->loadRevision(
      $fixture['author'],
      $fixture['revision']->getPHID());
    $reviewers = $revision->getReviewers();

    foreach ($expect as $key => $status) {
      $phid = $fixture[$key]->getPHID();
      $this->assertTrue(
        isset($reviewers[$phid]),
        pht('%s is a reviewer', $key));
      $this->assertEqual(
        $status,
        $reviewers[$phid]->getReviewerStatus(),
        $key);
    }
  }

  private function assertValidationError(
    array $fixture,
    array $xactions,
    string $label): void {

    $caught = null;
    try {
      $this->edit($fixture['bot'], $fixture['revision'], $xactions);
    } catch (PhabricatorApplicationTransactionValidationException $ex) {
      $caught = $ex;
    }

    $this->assertTrue($caught !== null, $label);
  }

  private function call(
    PhabricatorUser $user,
    string $method,
    array $params,
    $strict = true) {

    return id(new ConduitCall($method, $params, $strict))
      ->setUser($user)
      ->execute();
  }

}

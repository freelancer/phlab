<?php

/**
 * The `differential.inline.*` methods against real storage, a real revision
 * built from a raw diff, and three users: the revision author, a review bot
 * and another reviewer.
 */
final class DifferentialInlineConduitAPIMethodTestCase
  extends PhabricatorTestCase {

  // Full context, as "arc diff" uploads it. Line 4 on the new side is
  // "    if ($x == $y) {" (19 columns).
  const FULL_DIFF = <<<'EODIFF'
diff --git a/src/Example.php b/src/Example.php
--- a/src/Example.php
+++ b/src/Example.php
@@ -1,9 +1,9 @@
 <?php
 final class Example {
   public function isSame($x, $y) {
-    if ($x === $y) {
+    if ($x == $y) {
       return true;
     }
     return false;
   }
 }
EODIFF;

  // Two hunks, as "git diff -U1" would upload them.
  const PARTIAL_DIFF = <<<'EODIFF'
diff --git a/src/Partial.php b/src/Partial.php
--- a/src/Partial.php
+++ b/src/Partial.php
@@ -2,3 +2,3 @@
 a
-b
+B
 c
@@ -10,3 +10,3 @@
 d
-e
+E
 f
EODIFF;

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testEveryConduitMethodStillLoads(): void {
    // Conduit builds every method class on a cache miss, so one that fails
    // to construct would take down every Conduit call, not just its own.
    $methods = ConduitAPIMethod::loadAllConduitMethods();

    $names = [
      'differential.inline.create',
      'differential.inline.publish',
      'differential.inline.search',
      'differential.inline.setdone',
    ];

    foreach ($names as $name) {
      $this->assertTrue(isset($methods[$name]), $name);
      $this->assertTrue(
        (bool)$methods[$name]->getParamTypes(),
        pht('%s declares its parameters', $name));
    }

    $this->assertTrue(isset($methods['differential.createinline']));
  }

  public function testCreateWithRangeAndSuggestion(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot]);

    $created = $this->call($bot, 'differential.inline.create', [
      'revisionID' => (int)$revision->getID(),
      'filePath' => 'src/Example.php',
      'isNewFile' => true,
      'lineNumber' => 4,
      'startOffset' => 4,
      'endOffset' => 15,
      'content' => 'Use a strict comparison.',
      'suggestionText' => "    if (\$x === \$y) {\n",
    ]);

    $this->assertEqual('XCMT', $created['type']);
    $fields = $created['fields'];
    $this->assertEqual((int)$revision->getID(), $fields['revisionID']);
    $this->assertEqual('src/Example.php', $fields['filePath']);
    $this->assertEqual(true, $fields['isNewFile']);
    $this->assertEqual(4, $fields['lineNumber']);
    $this->assertEqual(0, $fields['lineLength']);
    $this->assertEqual(4, $fields['startOffset']);
    $this->assertEqual(15, $fields['endOffset']);
    $this->assertEqual('Use a strict comparison.', $fields['content']);
    $this->assertEqual("    if (\$x === \$y) {\n", $fields['suggestionText']);
    $this->assertEqual(true, $fields['isDraft']);
    $this->assertEqual(false, $fields['isDone']);
    $this->assertEqual($bot->getPHID(), $fields['authorPHID']);

    // Reading it back gives the same object.
    $this->assertEqual([$created], $this->search($bot, $revision));

    // The draft flag the web UI keeps for "you have unsubmitted comments".
    $this->assertTrue($this->hasDraft($bot, $revision));
  }

  public function testDraftsAreVisibleOnlyToTheirAuthorUntilPublished(): void {
    list($author, $bot, $other) = $this->newUsers(3);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot, $other]);

    $bot_inline = $this->newInline($bot, $revision, 'From the bot.');
    $other_inline = $this->newInline($other, $revision, 'Half-written.');

    $this->assertEqual(
      [$bot_inline['id']],
      $this->searchIDs($bot, $revision));
    $this->assertEqual(
      [$other_inline['id']],
      $this->searchIDs($other, $revision));
    $this->assertEqual([], $this->searchIDs($author, $revision));

    $published = $this->call($bot, 'differential.inline.publish', [
      'revisionID' => (int)$revision->getID(),
      'message' => 'Automated review.',
    ]);
    $this->assertEqual([$bot_inline['id']], $published['inlineIDs']);
    $this->assertFalse($this->hasDraft($bot, $revision));

    // Now everyone sees the bot's comment; the other draft stays private.
    $this->assertEqual(
      [$bot_inline['id']],
      $this->searchIDs($author, $revision));
    $this->assertEqual(
      [$other_inline['id'], $bot_inline['id']],
      $this->searchIDs($other, $revision));

    $seen = $this->search($author, $revision);
    $this->assertEqual(false, $seen[0]['fields']['isDraft']);

    // Nothing left to publish.
    $this->assertConduitError(
      'ERR-NOTHING-TO-PUBLISH',
      $bot,
      'differential.inline.publish',
      ['revisionID' => (int)$revision->getID()]);
  }

  public function testPublishOnlyTheNamedDrafts(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot]);

    $first = $this->newInline($bot, $revision, 'Publish me.');
    $second = $this->newInline($bot, $revision, 'Not yet.');
    $this->setDone($bot, $second['id'], true);

    $published = $this->call($bot, 'differential.inline.publish', [
      'revisionID' => (int)$revision->getID(),
      'inlineIDs' => [$first['id']],
    ]);
    $this->assertEqual([$first['id']], $published['inlineIDs']);
    $this->assertEqual(
      [$first['id']],
      $this->searchIDs($author, $revision));
    $this->assertTrue($this->hasDraft($bot, $revision));

    // The draft left behind keeps its pending "Done" check.
    $mine = $this->search($bot, $revision);
    $left = idx(ipull($mine, 'fields', 'id'), $second['id']);
    $this->assertEqual(
      PhabricatorInlineComment::STATE_DRAFT,
      $left['fixedState']);

    // Someone else's draft, or an already published one, is refused.
    $this->assertConduitError(
      'ERR-BAD-INLINE',
      $author,
      'differential.inline.publish',
      [
        'revisionID' => (int)$revision->getID(),
        'inlineIDs' => [$second['id']],
      ]);
    $this->assertConduitError(
      'ERR-BAD-INLINE',
      $bot,
      'differential.inline.publish',
      ['revisionID' => (int)$revision->getID(), 'inlineIDs' => [$first['id']]]);
  }

  public function testSearchPages(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot]);

    $ids = [];
    foreach (['one', 'two', 'three'] as $content) {
      $inline = $this->newInline($bot, $revision, $content);
      $ids[] = $inline['id'];
    }

    $page = $this->call($bot, 'differential.inline.search', [
      'revisionID' => (int)$revision->getID(),
      'limit' => 2,
    ]);
    $this->assertEqual(
      [$ids[2], $ids[1]],
      ipull($page['data'], 'id'));
    $this->assertTrue($page['cursor']['after'] !== null);

    $next = $this->call($bot, 'differential.inline.search', [
      'revisionID' => (int)$revision->getID(),
      'limit' => 2,
      'after' => $page['cursor']['after'],
    ]);
    $this->assertEqual([$ids[0]], ipull($next['data'], 'id'));
    $this->assertEqual(null, $next['cursor']['after']);
  }

  public function testSetDoneFollowsTheWebRule(): void {
    list($author, $bot, $other) = $this->newUsers(3);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot, $other]);

    $inline = $this->newInline($bot, $revision, 'Fix this.');
    $id = $inline['id'];

    // The comment author may mark their own draft.
    $own = $this->setDone($bot, $id, true);
    $this->assertEqual(true, $own['fields']['isDone']);
    $this->setDone($bot, $id, false);

    // The revision author cannot see, so cannot mark, someone's draft.
    $this->assertConduitError(
      'ERR-BAD-INLINE',
      $author,
      'differential.inline.setdone',
      ['inlineID' => $id, 'isDone' => true]);

    $this->call($bot, 'differential.inline.publish', [
      'revisionID' => (int)$revision->getID(),
    ]);

    // Once published, only the revision author may.
    $this->assertConduitError(
      'ERR-NOT-EDITABLE',
      $bot,
      'differential.inline.setdone',
      ['inlineID' => $id, 'isDone' => true]);
    $this->assertConduitError(
      'ERR-NOT-EDITABLE',
      $other,
      'differential.inline.setdone',
      ['inlineID' => $id, 'isDone' => true]);

    $checked = $this->setDone($author, $id, true);
    $this->assertEqual(true, $checked['fields']['isDone']);
    $this->assertEqual(true, $checked['fields']['isDonePending']);
    $this->assertEqual(
      PhabricatorInlineComment::STATE_DRAFT,
      $checked['fields']['fixedState']);

    // Repeating the call is a no-op, not a toggle.
    $again = $this->setDone($author, $id, true);
    $this->assertEqual($checked['fields'], $again['fields']);

    // The author's next submission makes it final.
    $this->call($author, 'differential.inline.publish', [
      'revisionID' => (int)$revision->getID(),
      'message' => 'Done.',
    ]);
    $final = $this->search($other, $revision);
    $this->assertEqual(
      PhabricatorInlineComment::STATE_DONE,
      $final[0]['fields']['fixedState']);
    $this->assertEqual(false, $final[0]['fields']['isDonePending']);
  }

  public function testCreateRejectsPlacesTheWebUICannot(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot]);
    $partial = $this->newRevision($author, self::PARTIAL_DIFF, [$bot]);

    $base = [
      'revisionID' => (int)$revision->getID(),
      'filePath' => 'src/Example.php',
      'isNewFile' => true,
      'lineNumber' => 4,
      'content' => 'Comment.',
    ];

    $cases = [
      'unknown file' => ['ERR-BAD-FILE', ['filePath' => 'src/Nope.php']],
      'line past the end' => ['ERR-BAD-LINE', ['lineNumber' => 10]],
      'line zero' => ['ERR-BAD-LINE', ['lineNumber' => 0]],
      'range past the end' => ['ERR-BAD-LINE', ['lineLength' => 6]],
      'only startOffset' => ['ERR-BAD-OFFSETS', ['startOffset' => 2]],
      'offset past the line' => [
        'ERR-BAD-OFFSETS',
        ['startOffset' => 0, 'endOffset' => 40],
      ],
      'suggestion on the old side' => [
        'ERR-BAD-SUGGESTION',
        ['isNewFile' => false, 'suggestionText' => "x\n"],
      ],
      'empty comment' => ['ERR-EMPTY-COMMENT', ['content' => ' ']],
      'deletion suggestion with no content' => [
        'ERR-EMPTY-COMMENT',
        ['content' => '', 'suggestionText' => ''],
      ],
      'no revision or diff' => [
        'ERR-NEED-DIFF',
        ['revisionID' => null],
      ],
    ];

    foreach ($cases as $label => $case) {
      list($code, $override) = $case;
      $this->assertConduitError(
        $code,
        $bot,
        'differential.inline.create',
        array_filter(
          $override + $base,
          function ($value) {
            return $value !== null;
          }),
        $label);
    }

    // A range over two lines may end at an earlier column than it starts.
    $multi = $this->call($bot, 'differential.inline.create', [
      'lineLength' => 1,
      'startOffset' => 10,
      'endOffset' => 3,
    ] + $base);
    $this->assertEqual(3, $multi['fields']['endOffset']);

    // On a partial-context diff, lines outside the hunks do not exist and
    // a suggestion could never be shown.
    $partial_base = [
      'revisionID' => (int)$partial->getID(),
      'filePath' => 'src/Partial.php',
      'lineNumber' => 11,
    ] + $base;
    $this->assertConduitError(
      'ERR-BAD-LINE',
      $bot,
      'differential.inline.create',
      ['lineNumber' => 7] + $partial_base,
      'between hunks');
    $this->assertConduitError(
      'ERR-BAD-SUGGESTION',
      $bot,
      'differential.inline.create',
      ['suggestionText' => "E2\n"] + $partial_base,
      'suggestion on partial context');
    $plain = $this->call($bot, 'differential.inline.create', $partial_base);
    $this->assertEqual(11, $plain['fields']['lineNumber']);
  }

  public function testPlainHTTPParametersAreParsed(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot]);

    // A plain HTTP key-value call sends every value as a string.
    $created = $this->call(
      $bot,
      'differential.inline.create',
      [
        'revisionID' => (string)$revision->getID(),
        'filePath' => 'src/Example.php',
        'isNewFile' => 'false',
        'lineNumber' => '3',
        'startOffset' => '2',
        'endOffset' => '5',
        'content' => 'Old side.',
      ],
      false);
    $this->assertEqual(false, $created['fields']['isNewFile']);
    $this->assertEqual(2, $created['fields']['startOffset']);

    $this->setDone($bot, $created['id'], true);
    $unchecked = $this->call(
      $bot,
      'differential.inline.setdone',
      [
        'inlineID' => (string)$created['id'],
        'isDone' => 'false',
      ],
      false);
    $this->assertEqual(false, $unchecked['fields']['isDone']);

    // In a JSON call, a string where a bool belongs is an error, not a
    // guess: "false" must never mark a comment done.
    $caught = null;
    try {
      $this->call($bot, 'differential.inline.setdone', [
        'inlineID' => $created['id'],
        'isDone' => 'false',
      ]);
    } catch (Throwable $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught !== null, 'strict string bool is rejected');
    $this->assertTrue(
      strpos($caught->getMessage(), 'isDone') !== false,
      'the error names the parameter');
    $still = $this->search($bot, $revision);
    $this->assertEqual(false, $still[0]['fields']['isDone']);
  }

  public function testRevisionAuthorsOwnInlinesStartDone(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot]);

    $own = $this->newInline($author, $revision, 'Note to self.');
    $this->assertEqual(true, $own['fields']['isDone']);
    $this->assertEqual(true, $own['fields']['isDonePending']);

    $theirs = $this->newInline($bot, $revision, 'Review.');
    $this->assertEqual(false, $theirs['fields']['isDone']);
  }

  public function testOldSideOfAMovedFileRoundTrips(): void {
    // "A" moves to "B", and "C" moves onto "A".
    $raw_diff = <<<'EODIFF'
diff --git a/src/A.php b/src/B.php
similarity index 60%
rename from src/A.php
rename to src/B.php
--- a/src/A.php
+++ b/src/B.php
@@ -1,3 +1,3 @@
 a
-b
+B
 c
diff --git a/src/C.php b/src/A.php
similarity index 60%
rename from src/C.php
rename to src/A.php
--- a/src/C.php
+++ b/src/A.php
@@ -1,3 +1,3 @@
 x
-y
+Y
 z
EODIFF;

    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, $raw_diff, [$bot]);

    $old = $this->call($bot, 'differential.inline.create', [
      'revisionID' => (int)$revision->getID(),
      'filePath' => 'src/A.php',
      'isNewFile' => false,
      'lineNumber' => 2,
      'content' => 'Old "A".',
    ]);
    $this->assertEqual('src/A.php', $old['fields']['filePath']);

    $new = $this->call($bot, 'differential.inline.create', [
      'revisionID' => (int)$revision->getID(),
      'filePath' => 'src/A.php',
      'isNewFile' => true,
      'lineNumber' => 2,
      'content' => 'New "A".',
    ]);
    $this->assertTrue(
      $old['fields']['changesetID'] !== $new['fields']['changesetID']);
  }

  public function testOldSideOfACopiedFileIsTheFileItself(): void {
    // "Src" is changed and also copied to "Dst", so both changesets have
    // "src/Src.php" as their old side.
    $raw_diff = <<<'EODIFF'
diff --git a/src/Src.php b/src/Src.php
--- a/src/Src.php
+++ b/src/Src.php
@@ -1,3 +1,3 @@
 a
-b
+B
 c
diff --git a/src/Src.php b/src/Dst.php
similarity index 60%
copy from src/Src.php
copy to src/Dst.php
--- a/src/Src.php
+++ b/src/Dst.php
@@ -1,3 +1,3 @@
 a
-b
+X
 c
EODIFF;

    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, $raw_diff, [$bot]);

    $old = $this->call($bot, 'differential.inline.create', [
      'revisionID' => (int)$revision->getID(),
      'filePath' => 'src/Src.php',
      'isNewFile' => false,
      'lineNumber' => 2,
      'content' => 'Old "Src".',
    ]);
    $new = $this->call($bot, 'differential.inline.create', [
      'revisionID' => (int)$revision->getID(),
      'filePath' => 'src/Src.php',
      'isNewFile' => true,
      'lineNumber' => 2,
      'content' => 'New "Src".',
    ]);

    $this->assertEqual('src/Src.php', $old['fields']['filePath']);
    $this->assertEqual(
      $new['fields']['changesetID'],
      $old['fields']['changesetID']);
  }

  public function testCreateThroughDiffID(): void {
    list($author, $bot, $other) = $this->newUsers(3);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot]);
    $first_diff = $revision->getActiveDiff();
    $revision = $this->updateRevision($author, $revision, self::FULL_DIFF);
    $other_revision = $this->newRevision($other, self::FULL_DIFF, [$bot]);

    $base = [
      'filePath' => 'src/Example.php',
      'isNewFile' => true,
      'lineNumber' => 4,
      'content' => 'On an older diff.',
    ];

    // An older diff of the revision is fine, as in the web UI.
    $old = $this->call(
      $bot,
      'differential.inline.create',
      ['diffID' => (int)$first_diff->getID()] + $base);
    $this->assertEqual((int)$first_diff->getID(), $old['fields']['diffID']);
    $this->assertEqual((int)$revision->getID(), $old['fields']['revisionID']);
    $this->assertTrue(
      $old['fields']['diffID'] !== (int)$revision->getActiveDiff()->getID());

    $this->assertConduitError(
      'ERR-BAD-DIFF',
      $bot,
      'differential.inline.create',
      [
        'revisionID' => (int)$other_revision->getID(),
        'diffID' => (int)$first_diff->getID(),
      ] + $base,
      'diff of another revision');

    // A diff that never became a revision.
    $changes = id(new ArcanistDiffParser())->parseDiff(self::FULL_DIFF);
    $loose = DifferentialDiff::newFromRawChanges($bot, $changes)
      ->setLintStatus(DifferentialLintStatus::LINT_AUTO_SKIP)
      ->setUnitStatus(DifferentialUnitStatus::UNIT_AUTO_SKIP)
      ->attachRevision(null)
      ->save();
    $this->assertConduitError(
      'ERR-BAD-DIFF',
      $bot,
      'differential.inline.create',
      ['diffID' => (int)$loose->getID()] + $base,
      'diff with no revision');
  }

  public function testRequiredAndTypedParameters(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot]);
    $inline = $this->newInline($bot, $revision, 'Comment.');

    $base = [
      'revisionID' => (int)$revision->getID(),
      'filePath' => 'src/Example.php',
      'isNewFile' => true,
      'lineNumber' => 4,
      'content' => 'Comment.',
    ];

    $cases = [
      'create without isNewFile' => [
        'differential.inline.create',
        array_diff_key($base, ['isNewFile' => true]),
        'isNewFile',
      ],
      'create with an array suggestion' => [
        'differential.inline.create',
        ['suggestionText' => ['x']] + $base,
        'suggestionText',
      ],
      'setdone without isDone' => [
        'differential.inline.setdone',
        ['inlineID' => $inline['id']],
        'isDone',
      ],
      'publish with inlineIDs not a list' => [
        'differential.inline.publish',
        ['revisionID' => (int)$revision->getID(), 'inlineIDs' => 'abc'],
        'inlineIDs',
      ],
    ];

    foreach ($cases as $label => $case) {
      list($method, $params, $key) = $case;

      $caught = null;
      try {
        $this->call($bot, $method, $params);
      } catch (Throwable $ex) {
        $caught = $ex;
      }

      $this->assertTrue($caught !== null, $label);
      $this->assertTrue(
        strpos($caught->getMessage(), $key) !== false,
        pht('%s: error names "%s"', $label, $key));
    }

    // Nothing was written by the rejected calls.
    $this->assertEqual([$inline['id']], $this->searchIDs($bot, $revision));
  }

  public function testSuggestionGetsAFinalNewline(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot]);

    $created = $this->call($bot, 'differential.inline.create', [
      'revisionID' => (int)$revision->getID(),
      'filePath' => 'src/Example.php',
      'isNewFile' => true,
      'lineNumber' => 4,
      'content' => 'No trailing newline.',
      'suggestionText' => '    if ($x === $y) {',
    ]);

    $this->assertEqual(
      "    if (\$x === \$y) {\n",
      $created['fields']['suggestionText']);
  }

  public function testSearchCursorMustBeVisible(): void {
    list($author, $bot, $other) = $this->newUsers(3);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot, $other]);

    $mine = $this->newInline($bot, $revision, 'Mine.');
    $theirs = $this->newInline($other, $revision, 'Theirs, unpublished.');

    $page = $this->call($bot, 'differential.inline.search', [
      'revisionID' => (int)$revision->getID(),
      'after' => (string)$mine['id'],
    ]);
    $this->assertEqual([], $page['data']);

    // Someone's draft and an ID that does not exist fail the same way.
    foreach ([$theirs['id'], 999999] as $id) {
      $this->assertConduitError(
        'ERR-BAD-CURSOR',
        $bot,
        'differential.inline.search',
        ['revisionID' => (int)$revision->getID(), 'before' => (string)$id],
        pht('cursor %d', $id));
    }
  }

  public function testPublishNeedsCanEdit(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [$bot], [
      ['type' => 'edit', 'value' => $author->getPHID()],
    ]);
    $inline = $this->newInline($bot, $revision, 'Locked revision.');

    $caught = null;
    try {
      $this->call($bot, 'differential.inline.publish', [
        'revisionID' => (int)$revision->getID(),
      ]);
    } catch (PhabricatorPolicyException $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught !== null, 'publish needs Can Edit');

    $drafts = $this->search($bot, $revision);
    $this->assertEqual($inline['id'], $drafts[0]['id']);
    $this->assertEqual(true, $drafts[0]['fields']['isDraft']);
  }

  public function testRevisionsYouCannotSeeAreInvisible(): void {
    list($author, $bot) = $this->newUsers(2);
    $revision = $this->newRevision($author, self::FULL_DIFF, [], [
      ['type' => 'view', 'value' => $author->getPHID()],
    ]);
    $inline = $this->newInline($author, $revision, 'Private.');
    $this->call($author, 'differential.inline.publish', [
      'revisionID' => (int)$revision->getID(),
    ]);

    $create = [
      'filePath' => 'src/Example.php',
      'isNewFile' => true,
      'lineNumber' => 4,
      'content' => 'Should not land.',
    ];

    $this->assertConduitError(
      'ERR-BAD-REVISION',
      $bot,
      'differential.inline.create',
      ['revisionID' => (int)$revision->getID()] + $create);
    $this->assertConduitError(
      'ERR-BAD-DIFF',
      $bot,
      'differential.inline.create',
      ['diffID' => (int)$revision->getActiveDiff()->getID()] + $create);
    $this->assertConduitError(
      'ERR-BAD-REVISION',
      $bot,
      'differential.inline.search',
      ['revisionID' => (int)$revision->getID()]);
    $this->assertConduitError(
      'ERR-BAD-INLINE',
      $bot,
      'differential.inline.setdone',
      ['inlineID' => $inline['id'], 'isDone' => true]);
    $this->assertConduitError(
      'ERR-BAD-REVISION',
      $bot,
      'differential.inline.publish',
      ['revisionID' => (int)$revision->getID()]);
  }

  private function newUsers(int $count): array {
    $users = [];
    for ($ii = 0; $ii < $count; $ii++) {
      $users[] = $this->generateNewTestUser();
    }
    return $users;
  }

  private function newRevision(
    PhabricatorUser $author,
    string $raw_diff,
    array $reviewers,
    array $extra_xactions = []): DifferentialRevision {

    $changes = id(new ArcanistDiffParser())->parseDiff($raw_diff);
    $diff = DifferentialDiff::newFromRawChanges($author, $changes)
      ->setLintStatus(DifferentialLintStatus::LINT_AUTO_SKIP)
      ->setUnitStatus(DifferentialUnitStatus::UNIT_AUTO_SKIP)
      ->attachRevision(null)
      ->save();

    $xactions = [
      ['type' => 'update', 'value' => $diff->getPHID()],
      ['type' => 'title', 'value' => 'Inline comment fixture'],
      ['type' => 'testPlan', 'value' => 'Fixture.'],
    ];
    if ($reviewers) {
      $xactions[] = [
        'type' => 'reviewers.add',
        'value' => mpull($reviewers, 'getPHID'),
      ];
    }

    $result = $this->call($author, 'differential.revision.edit', [
      'transactions' => array_merge($xactions, $extra_xactions),
    ]);

    return id(new DifferentialRevisionQuery())
      ->setViewer($author)
      ->withPHIDs([$result['object']['phid']])
      ->needActiveDiffs(true)
      ->executeOne();
  }

  private function updateRevision(
    PhabricatorUser $author,
    DifferentialRevision $revision,
    string $raw_diff): DifferentialRevision {

    $changes = id(new ArcanistDiffParser())->parseDiff($raw_diff);
    $diff = DifferentialDiff::newFromRawChanges($author, $changes)
      ->setLintStatus(DifferentialLintStatus::LINT_AUTO_SKIP)
      ->setUnitStatus(DifferentialUnitStatus::UNIT_AUTO_SKIP)
      ->attachRevision(null)
      ->save();

    $this->call($author, 'differential.revision.edit', [
      'objectIdentifier' => $revision->getPHID(),
      'transactions' => [
        ['type' => 'update', 'value' => $diff->getPHID()],
      ],
    ]);

    return id(new DifferentialRevisionQuery())
      ->setViewer($author)
      ->withPHIDs([$revision->getPHID()])
      ->needActiveDiffs(true)
      ->executeOne();
  }

  private function newInline(
    PhabricatorUser $user,
    DifferentialRevision $revision,
    string $content): array {

    return $this->call($user, 'differential.inline.create', [
      'revisionID' => (int)$revision->getID(),
      'filePath' => 'src/Example.php',
      'isNewFile' => true,
      'lineNumber' => 4,
      'content' => $content,
    ]);
  }

  private function setDone(
    PhabricatorUser $user,
    int $id,
    bool $is_done): array {

    return $this->call($user, 'differential.inline.setdone', [
      'inlineID' => $id,
      'isDone' => $is_done,
    ]);
  }

  private function search(
    PhabricatorUser $user,
    DifferentialRevision $revision): array {

    $result = $this->call($user, 'differential.inline.search', [
      'revisionID' => (int)$revision->getID(),
    ]);

    return $result['data'];
  }

  private function searchIDs(
    PhabricatorUser $user,
    DifferentialRevision $revision): array {

    return ipull($this->search($user, $revision), 'id');
  }

  private function hasDraft(
    PhabricatorUser $user,
    DifferentialRevision $revision): bool {

    PhabricatorDraftEngine::attachDrafts($user, [$revision]);

    return $revision->getHasDraft($user);
  }

  /**
   * `$strict` false is a plain HTTP key-value call, where every value
   * arrives as a string; true is a JSON call.
   */
  private function call(
    PhabricatorUser $user,
    string $method,
    array $params,
    $strict = true) {

    return id(new ConduitCall($method, $params, $strict))
      ->setUser($user)
      ->execute();
  }

  private function assertConduitError(
    string $code,
    PhabricatorUser $user,
    string $method,
    array $params,
    ?string $label = null): void {

    $caught = null;
    try {
      $this->call($user, $method, $params);
    } catch (ConduitException $ex) {
      $caught = $ex;
    }

    $label = nonempty($label, $method);
    $this->assertTrue($caught !== null, pht('%s: expected %s', $label, $code));
    $this->assertEqual($code, $caught->getMessage(), $label);
  }

}

<?php

/**
 * `herald.transcript.search` against real storage: Herald rules that the real
 * engine runs when a revision is created, and the transcripts it leaves
 * behind.
 */
final class HeraldTranscriptSearchConduitAPIMethodTestCase
  extends PhabricatorTestCase {

  const DIFF = <<<'EODIFF'
diff --git a/db/changes/add.sql b/db/changes/add.sql
--- a/db/changes/add.sql
+++ b/db/changes/add.sql
@@ -1,2 +1,2 @@
 -- fixture
-SELECT 1;
+SELECT 2;
EODIFF;

  const TEST_PLAN = 'secret-test-plan-text';

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testMethodLoads(): void {
    $methods = ConduitAPIMethod::loadAllConduitMethods();

    $this->assertTrue(isset($methods['herald.transcript.search']));
    $this->assertTrue(
      (bool)$methods['herald.transcript.search']->getParamTypes());
  }

  public function testReturnsWhatTheEngineDid(): void {
    list($author) = $this->newUsers(1);
    $this->newRule($author, 'Match on title', 'Herald fixture');
    $this->newRule($author, 'Never matches', 'no such words anywhere');
    $revision = $this->newRevision($author, 'Herald fixture revision');

    $result = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
    ]);

    $this->assertEqual((int)$revision->getID(), $result['revisionID']);
    $this->assertTrue(count($result['data']) >= 1, 'at least one run');

    $run = head($result['data']);
    $this->assertEqual(false, $run['dryRun']);
    $this->assertEqual(false, $run['detailsDiscarded']);
    $this->assertTrue($run['transcriptID'] > 0);
    $this->assertTrue($run['time'] > 0);
    $this->assertEqual(
      2,
      $run['rulesEvaluated'],
      'both rules, before filtering');

    // The default is the rules that matched.
    $names = ipull($run['rules'], 'name');
    $this->assertEqual(['Match on title'], array_values($names));

    $rule = head($run['rules']);
    $this->assertEqual('matched', $rule['result']);
    $this->assertEqual('H'.$rule['ruleID'], $rule['monogram']);
    $this->assertEqual('global', $rule['ruleType']);

    $this->assertEqual(1, count($rule['conditions']));
    $condition = head($rule['conditions']);
    $this->assertEqual('differential.revision.title', $condition['field']);
    $this->assertEqual('contains', $condition['condition']);
    $this->assertEqual('Herald fixture', $condition['testValue']);
    $this->assertEqual('passed', $condition['result']);

    $this->assertEqual(1, count($rule['actions']));
    $action = head($rule['actions']);
    $this->assertEqual('nothing', $action['action']);
    $this->assertTrue(is_bool($action['applied']));
    $this->assertTrue(is_array($action['effects']));
    $this->assertTrue(
      !array_key_exists('reason', $action),
      'the raw effect log is not returned');
  }

  public function testMatchedOnlyAndRuleIDs(): void {
    list($author) = $this->newUsers(1);
    $hit = $this->newRule($author, 'Hit', 'Herald fixture');
    $miss = $this->newRule($author, 'Miss', 'no such words anywhere');
    $revision = $this->newRevision($author, 'Herald fixture revision');

    $everything = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'matchedOnly' => false,
    ]);
    $run = head($everything['data']);
    $rules = ipull($this->at($run, 'rules'), null, 'ruleID');
    $this->assertEqual('matched', $rules[$hit->getID()]['result']);
    $this->assertEqual('failed', $rules[$miss->getID()]['result']);
    $this->assertEqual(
      'failed',
      $this->at(head($rules[$miss->getID()]['conditions']), 'result'));

    // Asking for a rule by id returns it even when it did not match: that is
    // how to ask why it did not.
    $one = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'ruleIDs' => [(int)$miss->getID()],
    ]);
    $this->assertEqual(
      [(int)$miss->getID()],
      array_values(ipull($this->at(head($one['data']), 'rules'), 'ruleID')));

    // ... unless the caller asks for matches only.
    $none = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'ruleIDs' => [(int)$miss->getID()],
      'matchedOnly' => true,
    ]);
    $this->assertEqual([], $none['data'], 'no run has that rule matching');

    // An empty list is no filter.
    $empty = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'ruleIDs' => [],
    ]);
    $this->assertEqual(
      [(int)$hit->getID()],
      array_values(ipull($this->at(head($empty['data']), 'rules'), 'ruleID')));

    // Plain HTTP callers send strings: "false" must read as false.
    $plain = $this->call($author, [
      'revisionID' => (string)$revision->getID(),
      'matchedOnly' => 'false',
    ], false);
    $this->assertEqual(
      2,
      count($this->at(head($plain['data']), 'rules')),
      'string "false" is false');
  }

  public function testRunsWithNothingToShowAreSkipped(): void {
    list($author) = $this->newUsers(1);
    $miss = $this->newRule($author, 'Miss', 'no such words anywhere');
    $revision = $this->newRevision($author, 'Herald fixture revision');

    $default = $this->call($author, ['revisionID' => (int)$revision->getID()]);
    $this->assertEqual([], $default['data'], 'no run matched anything');

    $all = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'matchedOnly' => false,
    ]);
    $this->assertTrue(count($all['data']) >= 1);
    $this->assertEqual(1, $this->at(head($all['data']), 'rulesEvaluated'));
  }

  public function testRunsNewestFirstAndDryRunsHidden(): void {
    list($author) = $this->newUsers(1);
    $this->newRule($author, 'Match on title', 'Herald fixture');
    $revision = $this->newRevision($author, 'Herald fixture revision');

    // Newest first, whatever number of runs the edit left.
    $real = $this->loadTranscripts($revision);
    $real_ids = array_map('intval', array_values(mpull($real, 'getID')));
    $newest = head($real);

    // A later run, as an update would leave, and a Test Console dry run
    // after that.
    $second = $this->copyTranscript($newest, 1000, false);
    $dry = $this->copyTranscript($newest, 2000, true);

    $result = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'limit' => 10,
    ]);
    $this->assertEqual(
      array_merge([(int)$second->getID()], $real_ids),
      array_values(ipull($result['data'], 'transcriptID')),
      'newest first, dry run hidden');

    $with_dry = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'includeDryRuns' => true,
      'limit' => 10,
    ]);
    $this->assertEqual(
      array_merge([(int)$dry->getID(), (int)$second->getID()], $real_ids),
      array_values(ipull($with_dry['data'], 'transcriptID')));
    $this->assertEqual(true, $this->at(head($with_dry['data']), 'dryRun'));

    $limited = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'limit' => 1,
    ]);
    $this->assertEqual(
      [(int)$second->getID()],
      array_values(ipull($limited['data'], 'transcriptID')));
  }

  public function testDryRunsCannotCrowdOutRealRuns(): void {
    list($author) = $this->newUsers(1);
    $this->newRule($author, 'Match on title', 'Herald fixture');
    $revision = $this->newRevision($author, 'Herald fixture revision');

    $newest = head($this->loadTranscripts($revision));
    for ($ii = 1; $ii <= 40; $ii++) {
      $this->copyTranscript($newest, 1000 + $ii, true);
    }

    $result = $this->call($author, ['revisionID' => (int)$revision->getID()]);
    $this->assertEqual(
      (int)$newest->getID(),
      $this->at(head($result['data']), 'transcriptID'),
      'the real run is still found behind 40 dry runs');
  }

  public function testObjectTranscriptIsNotReturned(): void {
    list($author) = $this->newUsers(1);
    $this->newRule($author, 'Match on title', 'Herald fixture');
    // The engine stores only the fields that conditions read, so a rule that
    // reads the test plan puts it in the object transcript.
    $this->newRule(
      $author,
      'Reads the test plan',
      'zzz-never-in-a-test-plan',
      'differential.revision.test-plan');
    $revision = $this->newRevision($author, 'Herald fixture revision');

    $transcript = head($this->loadTranscripts($revision));
    $stored = json_encode($transcript->getObjectTranscript()->getFields());
    $this->assertTrue(
      strpos($stored, self::TEST_PLAN) !== false,
      'the fixture is meaningful: the object transcript holds the test plan');

    $result = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'matchedOnly' => false,
      'includeDryRuns' => true,
    ]);
    $encoded = json_encode($result);
    $this->assertTrue(strpos($encoded, self::TEST_PLAN) === false);
    $this->assertTrue(strpos($encoded, 'objectTranscript') === false);
  }

  public function testHandlesNameWhatTheViewerCanSee(): void {
    list($author) = $this->newUsers(1);
    $bogus = 'PHID-PROJ-doesnotexistatall';
    $this->newRule(
      $author,
      'Author rule',
      [$author->getPHID(), $bogus],
      'differential.revision.author',
      HeraldAdapter::CONDITION_IS_ANY);
    $revision = $this->newRevision($author, 'Herald fixture revision');

    $result = $this->call($author, ['revisionID' => (int)$revision->getID()]);

    $rule = head($this->at(head($result['data']), 'rules'));
    $this->assertEqual('Author rule', $rule['name']);
    $condition_value = $this->at(head($rule['conditions']), 'testValue');
    $this->assertTrue(
      in_array($author->getPHID(), $condition_value),
      'the raw value is there');

    $this->assertTrue(isset($result['handles'][$author->getPHID()]));
    $this->assertTrue(
      strpos(
        $result['handles'][$author->getPHID()]['name'],
        $author->getUsername()) !== false,
      'the handle is named');
    $this->assertTrue(
      !isset($result['handles'][$bogus]),
      'an object that does not resolve has no entry');
  }

  public function testTextValuesAreCapped(): void {
    list($author) = $this->newUsers(1);
    $long = str_repeat('x', 400);
    $rule = $this->newRule($author, str_repeat('n', 255), $long);
    $revision = $this->newRevision($author, 'Herald fixture revision');

    $result = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'ruleIDs' => [(int)$rule->getID()],
    ]);

    $found = head($this->at(head($result['data']), 'rules'));
    $this->assertTrue(
      phutil_utf8_strlen($found['name']) <= 200,
      'rule name is capped');
    $text_value = $this->at(head($found['conditions']), 'testValue');
    $this->assertTrue(
      phutil_utf8_strlen($text_value) <= 200,
      'text condition value is capped');
  }

  public function testPersonalRulesSayTheyArePersonal(): void {
    list($author, $other) = $this->newUsers(2);
    $this->newRule(
      $other,
      'Someone else personal rule',
      'Herald fixture',
      'differential.revision.title',
      HeraldAdapter::CONDITION_CONTAINS,
      HeraldRuleTypeConfig::RULE_TYPE_PERSONAL);
    $revision = $this->newRevision($author, 'Herald fixture revision');

    $result = $this->call($author, [
      'revisionID' => (int)$revision->getID(),
      'matchedOnly' => false,
    ]);

    $types = [];
    foreach ($result['data'] as $run) {
      foreach ($run['rules'] as $rule) {
        $types[$rule['name']] = $rule['ruleType'];
      }
    }
    $this->assertEqual(
      HeraldRuleTypeConfig::RULE_TYPE_PERSONAL,
      idx($types, 'Someone else personal rule'));
  }

  public function testRevisionsYouCannotSeeAreInvisible(): void {
    list($author, $other) = $this->newUsers(2);
    $this->newRule($author, 'Match on title', 'Herald fixture');
    $revision = $this->newRevision(
      $author,
      'Herald fixture revision',
      [['type' => 'view', 'value' => $author->getPHID()]]);

    $visible = $this->call($author, ['revisionID' => (int)$revision->getID()]);
    $this->assertTrue(count($visible['data']) >= 1);

    $this->assertConduitError(
      'ERR-BAD-REVISION',
      $other,
      ['revisionID' => (int)$revision->getID()],
      'a revision the viewer cannot see');
    $this->assertConduitError(
      'ERR-BAD-REVISION',
      $author,
      ['revisionID' => 999999999],
      'a revision that does not exist');
  }

  public function testNoRulesIsEmptyNotAnError(): void {
    list($author) = $this->newUsers(1);
    $revision = $this->newRevision($author, 'Herald fixture revision');

    $result = $this->call($author, ['revisionID' => (int)$revision->getID()]);
    $this->assertEqual([], $result['data']);
    $this->assertEqual([], $result['handles']);
  }

  public function testRevisionIDIsRequired(): void {
    list($author) = $this->newUsers(1);

    $caught = null;
    try {
      $this->call($author, []);
    } catch (Throwable $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught !== null);
  }

  private function newUsers(int $count): array {
    $users = [];
    for ($ii = 0; $ii < $count; $ii++) {
      $users[] = $this->generateNewTestUser();
    }
    return $users;
  }

  /**
   * A rule that fires "do nothing" when `$field` meets `$condition` for
   * `$value`, run by the real engine on every revision change.
   */
  private function at(array $array, $key) {
    return $array[$key];
  }

  private function newRule(
    PhabricatorUser $author,
    $name,
    $value,
    $field = 'differential.revision.title',
    $condition = HeraldAdapter::CONDITION_CONTAINS,
    $rule_type = HeraldRuleTypeConfig::RULE_TYPE_GLOBAL) {

    $rule = id(new HeraldRule())
      ->setName($name)
      ->setAuthorPHID($author->getPHID())
      ->setContentType(
        id(new HeraldDifferentialRevisionAdapter())->getAdapterContentType())
      ->setMustMatchAll(1)
      ->setRepetitionPolicy(HeraldRule::REPEAT_EVERY)
      ->setRuleType($rule_type)
      ->save();

    $rule->saveConditions([
      id(new HeraldCondition())
        ->setFieldName($field)
        ->setFieldCondition($condition)
        ->setValue($value),
    ]);
    $rule->saveActions([
      id(new HeraldActionRecord())
        ->setAction('nothing')
        ->setTarget(true),
    ]);

    return $rule;
  }

  private function newRevision(
    PhabricatorUser $author,
    string $title,
    array $extra_xactions = []): DifferentialRevision {

    $changes = id(new ArcanistDiffParser())->parseDiff(self::DIFF);
    $diff = DifferentialDiff::newFromRawChanges($author, $changes)
      ->setLintStatus(DifferentialLintStatus::LINT_AUTO_SKIP)
      ->setUnitStatus(DifferentialUnitStatus::UNIT_AUTO_SKIP)
      ->attachRevision(null)
      ->save();

    $result = $this->call(
      $author,
      [
        'transactions' => array_merge(
          [
            ['type' => 'update', 'value' => $diff->getPHID()],
            ['type' => 'title', 'value' => $title],
            ['type' => 'testPlan', 'value' => self::TEST_PLAN],
          ],
          $extra_xactions),
      ],
      true,
      'differential.revision.edit');

    return id(new DifferentialRevisionQuery())
      ->setViewer($author)
      ->withPHIDs([$result['object']['phid']])
      ->executeOne();
  }

  /** Newest first, as the method sees them. */
  private function loadTranscripts(DifferentialRevision $revision): array {
    return id(new HeraldTranscriptQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withObjectPHIDs([$revision->getPHID()])
      ->execute();
  }

  private function copyTranscript(
    HeraldTranscript $source,
    int $seconds_later,
    bool $dry_run): HeraldTranscript {

    $copy = id(new HeraldTranscript())
      ->setObjectPHID($source->getObjectPHID())
      ->setObjectTranscript($source->getObjectTranscript())
      ->setRuleTranscripts($source->getRuleTranscripts())
      ->setConditionTranscripts($source->getConditionTranscripts())
      ->setApplyTranscripts($source->getApplyTranscripts())
      ->setTime($source->getTime() + $seconds_later)
      ->setHost($source->getHost())
      ->setDuration($source->getDuration())
      ->setDryRun($dry_run ? 1 : 0);
    $copy->save();

    return $copy;
  }

  private function call(
    PhabricatorUser $user,
    array $params,
    $strict = true,
    $method = 'herald.transcript.search') {

    return id(new ConduitCall($method, $params, $strict))
      ->setUser($user)
      ->execute();
  }

  private function assertConduitError(
    string $code,
    PhabricatorUser $user,
    array $params,
    string $label): void {

    $caught = null;
    try {
      $this->call($user, $params);
    } catch (ConduitException $ex) {
      $caught = $ex;
    }

    $this->assertTrue($caught !== null, pht('%s: expected %s', $label, $code));
    $this->assertEqual($code, $caught->getMessage(), $label);
  }

}

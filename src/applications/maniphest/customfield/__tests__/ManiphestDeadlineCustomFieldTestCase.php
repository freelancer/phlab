<?php

/**
 * Setting a task's deadline through `maniphest.edit`, against real storage,
 * compared with what the web edit form does.
 */
final class ManiphestDeadlineCustomFieldTestCase
  extends PhabricatorTestCase {

  const KEY = 'maniphest:deadline';

  // 2026-11-20 00:00 in Australia/Sydney (AEDT, UTC+11) and in
  // America/Los_Angeles (PST, UTC-8).
  const SYDNEY_MIDNIGHT = 1795093200;
  const LOS_ANGELES_MIDNIGHT = 1795161600;

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testEditOffersTheDeadline(): void {
    $user = $this->generateNewTestUser();

    $types = (new ManiphestEditEngine())
      ->setViewer($user)
      ->getConduitEditTypes();

    $this->assertTrue(isset($types[self::KEY]));
    $this->assertEqual('epoch|date', $types[self::KEY]->getConduitType());
  }

  public function testSetByEpochSchedulesTheReminder(): void {
    $user = $this->generateNewTestUser();
    $epoch = 1800000000;

    $task = $this->newTask($user, [$this->deadline($epoch)]);

    $value = $this->searchDeadline($user, $task);
    $this->assertEqual($epoch, $value['epoch']);
    $this->assertTrue(is_string($value['triggerPHID']));

    $trigger = $this->loadTrigger($value['triggerPHID']);
    $this->assertTrue($trigger !== null);
    $this->assertEqual(
      $epoch - phutil_units('24 hours in seconds'),
      $trigger->getClock()->getProperty('epoch'));
    $this->assertEqual(
      ManiphestDeadlineReminderWorker::class,
      $trigger->getAction()->getProperty('class'));
    $this->assertEqual(
      $task['phid'],
      idx($trigger->getAction()->getProperty('data'), 'objectPHID'));
  }

  public function testChangeMovesTheSameReminder(): void {
    $user = $this->generateNewTestUser();

    $task = $this->newTask($user, [$this->deadline(1800000000)]);
    $before = $this->searchDeadline($user, $task);

    $this->edit($user, $task, [$this->deadline(1800086400)]);
    $after = $this->searchDeadline($user, $task);

    $this->assertEqual(1800086400, $after['epoch']);
    $this->assertEqual($before['triggerPHID'], $after['triggerPHID']);
    $this->assertEqual(
      1800086400 - phutil_units('24 hours in seconds'),
      $this->loadTrigger($after['triggerPHID'])
        ->getClock()
        ->getProperty('epoch'));
  }

  public function testNullRemovesTheDeadlineAndItsReminder(): void {
    $user = $this->generateNewTestUser();

    $task = $this->newTask($user, [$this->deadline(1800000000)]);
    $trigger_phid = idx($this->searchDeadline($user, $task), 'triggerPHID');

    $this->edit($user, $task, [$this->deadline(null)]);

    $this->assertEqual(
      ['epoch' => null, 'triggerPHID' => null],
      $this->searchDeadline($user, $task));
    $this->assertEqual(null, $this->loadTrigger($trigger_phid));
    $this->assertEqual(
      [[null, 1800000000], [1800000000, null]],
      $this->deadlineChanges($user, $task));
  }

  public function testSameDeadlineAgainChangesNothing(): void {
    $user = $this->generateNewTestUser();

    $task = $this->newTask($user, [$this->deadline(1800000000)]);
    $this->edit($user, $task, [$this->deadline(1800000000)]);
    $this->edit($user, $task, [$this->deadline(1800086400)]);

    $this->assertEqual(
      [[null, 1800000000], [1800000000, 1800086400]],
      $this->deadlineChanges($user, $task));
  }

  public function testDateIsTheStartOfThatDayInTheCallersTimezone(): void {
    $sydney = $this->generateNewTestUser()
      ->overrideTimezoneIdentifier('Australia/Sydney');
    $los_angeles = $this->generateNewTestUser()
      ->overrideTimezoneIdentifier('America/Los_Angeles');

    $task = $this->newTask($sydney, [$this->deadline('2026-11-20')]);
    $this->assertEqual(
      self::SYDNEY_MIDNIGHT,
      idx($this->searchDeadline($sydney, $task), 'epoch'));

    $this->edit($los_angeles, $task, [$this->deadline('2026-11-20')]);
    $this->assertEqual(
      self::LOS_ANGELES_MIDNIGHT,
      idx($this->searchDeadline($sydney, $task), 'epoch'));
  }

  public function testDateGivesTheEpochTheWebFormGives(): void {
    foreach (['Australia/Sydney', 'America/Los_Angeles', 'UTC'] as $zone) {
      $user = $this->generateNewTestUser()->overrideTimezoneIdentifier($zone);

      // What the edit form posts when the user picks the date: the date in
      // their date format, the hidden "12:00 AM" time, and the enable box.
      $request = (new AphrontRequest('phabricator.localhost', '/'))
        ->setUser($user)
        ->setRequestData([
          self::KEY.'_d' => '2026-11-20',
          self::KEY.'_t' => '12:00 AM',
          self::KEY.'_e' => '1',
        ]);
      $field = (new ManiphestDeadlineCustomField())->setViewer($user);
      $field->readValueFromRequest($request);

      $task = $this->newTask($user, [$this->deadline('2026-11-20')]);

      $this->assertEqual(
        idx($field->getValue(), 'epoch'),
        idx($this->searchDeadline($user, $task), 'epoch'),
        $zone);
    }
  }

  public function testFormEncodedEpochIsAccepted(): void {
    $user = $this->generateNewTestUser();

    $task = $this->newTask($user, [$this->deadline('1800000000')], false);

    $this->assertEqual(
      1800000000,
      idx($this->searchDeadline($user, $task), 'epoch'));
  }

  public function testBadValuesAreRefusedAndChangeNothing(): void {
    $user = $this->generateNewTestUser();
    $task = $this->newTask($user, [$this->deadline(1800000000)]);

    $bad = [
      'zero' => 0,
      'negative' => -86400,
      'before 2000' => 946684740,
      'a date sent as an epoch' => 20261120,
      'milliseconds' => 1800000000000,
      'seconds past the minute' => 1800000005,
      'reminder after 2106' => 4295053696,
      'impossible date' => '2026-02-30',
      'month 13' => '2026-13-01',
      'date and time' => '2026-11-20 09:00',
      'unpadded date' => '2026-1-5',
      'words' => 'next friday',
      'string epoch, strictly typed' => '1800000000',
      'float' => 1800000000.5,
      'bool' => true,
      'list' => [1800000000],
    ];

    $messages = [];
    foreach ($bad as $label => $value) {
      $caught = null;
      try {
        $this->edit($user, $task, [$this->deadline($value)]);
      } catch (Throwable $ex) {
        $caught = $ex;
      }

      $this->assertTrue($caught !== null, $label);
      $this->assertTrue(
        strpos($caught->getMessage(), self::KEY) !== false,
        pht('%s: %s', $label, $caught->getMessage()));
      $messages[$label] = $caught->getMessage();
    }

    // The message says what is accepted.
    $this->assertTrue(
      strpos($messages['words'], '"YYYY-MM-DD"') !== false,
      $messages['words']);
    $this->assertTrue(
      strpos(
        $messages['impossible date'],
        '"2026-02-30" is not a date') !== false,
      $messages['impossible date']);

    $this->assertEqual(
      1800000000,
      idx($this->searchDeadline($user, $task), 'epoch'));
    $this->assertEqual(1, count($this->deadlineChanges($user, $task)));
  }

  public function testOneDeadlinePerEdit(): void {
    $user = $this->generateNewTestUser();
    $task = $this->newTask($user, []);
    $triggers = $this->countTriggers();

    $caught = null;
    try {
      $this->edit($user, $task, [
        $this->deadline(1800000000),
        $this->deadline(1800086400),
      ]);
    } catch (Throwable $ex) {
      $caught = $ex;
    }

    $this->assertTrue($caught !== null);
    $this->assertTrue(
      strpos($caught->getMessage(), 'at most once per edit') !== false,
      $caught->getMessage());
    $this->assertEqual(
      ['epoch' => null, 'triggerPHID' => null],
      $this->searchDeadline($user, $task));
    $this->assertEqual($triggers, $this->countTriggers());
  }

  public function testSearchShapeIsUnchanged(): void {
    $user = $this->generateNewTestUser();

    $task = $this->newTask($user, []);
    $this->assertEqual(
      ['epoch' => null, 'triggerPHID' => null],
      $this->searchDeadline($user, $task));

    $this->edit($user, $task, [$this->deadline(1800000000)]);
    $this->assertEqual(
      ['epoch', 'triggerPHID'],
      array_keys($this->searchDeadline($user, $task)));
  }

  private function deadline($value): array {
    return ['type' => self::KEY, 'value' => $value];
  }

  private function newTask(
    PhabricatorUser $user,
    array $xactions,
    $strict = true): array {

    $result = $this->call(
      $user,
      'maniphest.edit',
      [
        'transactions' => array_merge(
          [['type' => 'title', 'value' => 'Deadline test']],
          $xactions),
      ],
      $strict);

    return $result['object'];
  }

  private function edit(PhabricatorUser $user, array $task, array $xactions) {
    return $this->call(
      $user,
      'maniphest.edit',
      [
        'objectIdentifier' => $task['phid'],
        'transactions' => $xactions,
      ]);
  }

  private function searchDeadline(PhabricatorUser $user, array $task): array {
    $result = $this->call(
      $user,
      'maniphest.search',
      ['constraints' => ['phids' => [$task['phid']]]]);

    $task = head($result['data']);
    return $task['fields'][self::KEY];
  }

  /**
   * Each deadline transaction on the task, oldest first, as [old, new] epochs.
   */
  private function deadlineChanges(PhabricatorUser $user, array $task): array {
    $xactions = (new ManiphestTransactionQuery())
      ->setViewer($user)
      ->withObjectPHIDs([$task['phid']])
      ->withTransactionTypes([PhabricatorTransactions::TYPE_CUSTOMFIELD])
      ->execute();

    $changes = [];
    foreach (array_reverse($xactions) as $xaction) {
      if ($xaction->getMetadataValue('customfield:key') !== self::KEY) {
        continue;
      }
      $changes[] = [
        $this->epochOf($xaction->getOldValue()),
        $this->epochOf($xaction->getNewValue()),
      ];
    }

    return $changes;
  }

  private function epochOf(?string $value): ?int {
    if ($value === null) {
      return null;
    }
    return idx(phutil_json_decode($value), 'epoch');
  }

  private function countTriggers(): int {
    return count(
      (new PhabricatorWorkerTriggerQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->execute());
  }

  private function loadTrigger(string $phid): ?PhabricatorWorkerTrigger {
    return (new PhabricatorWorkerTriggerQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs([$phid])
      ->executeOne();
  }

  private function call(
    PhabricatorUser $user,
    string $method,
    array $params,
    $strict = true) {

    return (new ConduitCall($method, $params, $strict))
      ->setUser($user)
      ->execute();
  }

}

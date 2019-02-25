<?php

$table = new PhabricatorWorkerTrigger();
$conn  = $table->establishConnection('w');

echo tsprintf(
  "%s\n",
  pht(
    "Updating triggers from '%s' to '%s'.",
    'ManiphestDeadlineReminderTriggerClock',
    'PhabricatorOneTimeTriggerClock'));

foreach (new LiskMigrationIterator($table) as $trigger) {
  if ($trigger->getClockClass() !== 'ManiphestDeadlineReminderTriggerClock') {
    continue;
  }

  $task_phid = idx($trigger->getClockProperties(), 'taskPHID');
  $task = (new ManiphestTask())->loadOneWhere('phid = %s', $task_phid);

  $field = PhabricatorCustomField::getObjectField(
    $task,
    PhabricatorCustomField::ROLE_VIEW,
    'maniphest:deadline');

  (new PhabricatorCustomFieldStorageQuery())
    ->addField($field)
    ->execute();

  $epoch = phutil_json_decode($field->getValueForStorage())['epoch'];
  $clock = new PhabricatorOneTimeTriggerClock([
    'epoch' => ($epoch - phutil_units('24 hours in seconds')),
  ]);

  $trigger->setClock($clock);
  $trigger->save();
}

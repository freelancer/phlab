<?php

$table = new HeraldActionRecord();
$conn = $table->establishConnection('w');

foreach (new LiskMigrationIterator($table) as $action_record) {
  $action = $action_record->getAction();
  $target = $action_record->getTarget();

  if ($action == 'hipchat.notification') {
    $rocket_target = strtolower(str_replace(' ', '-', $target));

    queryfx(
      $conn,
      'UPDATE %T SET action = %s, target = %s WHERE id = %d',
      $table->getTableName(),
      HeraldRocketChatNotificationAction::ACTIONCONST,
      json_encode($rocket_target),
      $action_record->getID());
  }
}

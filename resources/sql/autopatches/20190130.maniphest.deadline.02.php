<?php

$custom_field = new ManiphestDeadlineCustomField();

$table = new ManiphestTransaction();
$conn  = $table->establishConnection('w');

foreach (new LiskMigrationIterator($table) as $xaction) {
  if ($xaction->getTransactionType() !== PhabricatorTransactions::TYPE_CUSTOMFIELD) {
    continue;
  }

  if ($xaction->getMetadataValue('customfield:key') !== $custom_field->getFieldKey()) {
    continue;
  }

  $old_value = $xaction->getOldValue();
  if (is_numeric($old_value)) {
    $old_value = phutil_json_encode(['epoch' => (int)$old_value]);
    $xaction->setOldValue($old_value);
  }

  $new_value = $xaction->getNewValue();
  if (is_numeric($new_value)) {
    $new_value = phutil_json_encode(['epoch' => (int)$new_value]);
    $xaction->setNewValue($new_value);
  }

  $xaction->save();
}

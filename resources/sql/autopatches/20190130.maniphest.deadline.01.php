<?php

$custom_field = new ManiphestDeadlineCustomField();

$table = $custom_field->newStorageObject();
$conn  = $table->establishConnection('w');

foreach (new LiskMigrationIterator($table) as $field_storage) {
  if ($field_storage->getFieldIndex() !== $custom_field->getFieldIndex()) {
    continue;
  }

  $epoch = $field_storage->getFieldValue();

  // If `$epoch` is not numeric then possibly the storage has
  // already been migrated.
  if (!is_numeric($epoch)) {
    continue;
  }

  $value = phutil_json_encode(['epoch' => (int)$epoch]);
  $field_storage->setFieldValue($value);
  $field_storage->save();
}

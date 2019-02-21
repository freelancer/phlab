<?php

final class ManiphestDeadlineReminderTriggerClock
  extends PhabricatorTriggerClock {

  public function validateProperties(array $properties): void {
    PhutilTypeSpec::checkMap(
      $properties,
      [
        'taskPHID' => 'string',
      ]);
  }

  public function getNextEventEpoch($last_epoch, $is_reschedule): ?int {
    $viewer = PhabricatorUser::getOmnipotentUser();

    $task = (new ManiphestTaskQuery())
      ->setViewer($viewer)
      ->withPHIDs([$this->getProperty('taskPHID')])
      ->executeOne();

    // TODO: Should we throw an exception here?
    if ($task === null) {
      return null;
    }

    $field = PhabricatorCustomField::getObjectField(
      $task,
      PhabricatorCustomField::ROLE_DEFAULT,
      ManiphestDeadlineCustomField::FIELD_KEY);

    (new PhabricatorCustomFieldStorageQuery())
      ->addField($field)
      ->execute();

    $next_epoch = $field->getEpoch() - phutil_units('24 hours in seconds');

    if ($last_epoch < $next_epoch) {
      return $next_epoch;
    } else {
      return null;
    }
  }

}

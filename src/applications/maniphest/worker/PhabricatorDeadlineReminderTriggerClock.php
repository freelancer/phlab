<?php

final class PhabricatorDeadlineReminderTriggerClock
  extends PhabricatorTriggerClock {

  public function validateProperties(array $properties): void {
    PhutilTypeSpec::checkMap(
      $properties,
      [
        'taskPHID'       => 'string',
      ]);
  }

  public function getNextEventEpoch($last_epoch, $is_reschedule): ?int {
    $viewer = PhabricatorUser::getOmnipotentUser();

    $task = (new ManiphestTaskQuery())
      ->setViewer($viewer)
      ->withPHIDs([$this->getProperty('taskPHID')])
      ->executeOne();

    if ($task == null) {
      return null;
    }

    $field = PhabricatorCustomField::getObjectField(
      $task, PhabricatorCustomField::ROLE_VIEW, 'maniphest:deadline');

    (new PhabricatorCustomFieldStorageQuery())
      ->addField($field)
      ->execute();

    $next_epoch = $field->getEpoch() - phutil_units('24 hours in seconds');

    return ($last_epoch < $next_epoch) ? $next_epoch : null;
  }

}

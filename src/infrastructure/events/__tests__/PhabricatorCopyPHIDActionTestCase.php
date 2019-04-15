<?php

final class PhabricatorCopyPHIDActionTestCase extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testHandleEventWithoutObject(): void {
    $user  = $this->generateNewTestUser();
    $event = $this->generateEvent($user);

    $this->handleEvent($event);

    $actions = $event->getValue('actions');
    $this->assertTrue(empty($actions));
  }

  public function testHandleEventWithObject(): void {
    $user = $this->generateNewTestUser();
    $task = ManiphestTask::initializeNewTask($user)
      ->save();

    $event = $this->generateEvent($user)
      ->setValue('object', $task);

    $this->handleEvent($event);

    $actions = $event->getValue('actions');
    $this->assertEqual(1, count($actions));

    $action = head($actions);
    $this->assertEqual($user, $action->getViewer());
    $this->assertEqual(['text' => $task->getPHID()], $action->getMetadata());
  }

  private function generateEvent(PhabricatorUser $user): PhabricatorEvent {
    $type  = PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS;
    $event = (new PhabricatorEvent($type))->setUser($user);

    return $event;
  }

  private function handleEvent(PhabricatorEvent $event): void {
    $listener = new PhabricatorCopyPHIDAction();
    $listener->handleEvent($event);
  }

}

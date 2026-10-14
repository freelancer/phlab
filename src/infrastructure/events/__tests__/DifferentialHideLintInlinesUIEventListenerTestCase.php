<?php

final class DifferentialHideLintInlinesUIEventListenerTestCase
  extends PhabricatorTestCase {

  const BEHAVIOR = 'differential-hide-lint-inlines';

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  protected function willRunOneTest($test_method_name): void {
    parent::willRunOneTest($test_method_name);

    // The response is static, so drop behaviors that earlier tests left.
    $this->getInitializedBehaviors();
  }

  public function testHandleEventWithoutObject(): void {
    $user = $this->generateNewTestUser();

    $this->handleEvent($this->generateEvent($user));

    $this->assertEqual(null, $this->getBehaviorConfigs());
  }

  public function testHandleEventWithDifferentialRevision(): void {
    $user = $this->generateNewTestUser();
    $revision = DifferentialRevision::initializeNewRevision($user);

    $this->handleEvent($this->generateEvent($user, $revision));

    $this->assertEqual(
      [['name' => 'Hide Lint Inlines']],
      $this->getBehaviorConfigs());
  }

  public function testHandleEventWithDifferentialDiff(): void {
    $user = $this->generateNewTestUser();
    $diff = DifferentialDiff::initializeNewDiff($user);

    $this->handleEvent($this->generateEvent($user, $diff));

    $this->assertEqual(null, $this->getBehaviorConfigs());
  }

  public function testHandleEventWithRepositoryCommit(): void {
    $user = $this->generateNewTestUser();
    $commit = new PhabricatorRepositoryCommit();

    $this->handleEvent($this->generateEvent($user, $commit));

    $this->assertEqual(null, $this->getBehaviorConfigs());
  }

  public function testHandleEventWithUnrelatedObject(): void {
    $user = $this->generateNewTestUser();
    $task = ManiphestTask::initializeNewTask($user);

    $this->handleEvent($this->generateEvent($user, $task));

    $this->assertEqual(null, $this->getBehaviorConfigs());
  }

  private function generateEvent(
    PhabricatorUser $user,
    $object = null): PhabricatorEvent {

    $type = PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS;
    $event = (new PhabricatorEvent($type))->setUser($user);

    if ($object !== null) {
      $event->setValue('object', $object);
    }

    return $event;
  }

  private function handleEvent(PhabricatorEvent $event): void {
    $listener = new DifferentialHideLintInlinesUIEventListener();
    $listener->handleEvent($event);
  }

  /**
   * Returns the configs the behavior was initialized with, or null if it was
   * not initialized.
   */
  private function getBehaviorConfigs(): ?array {
    return idx($this->getInitializedBehaviors(), self::BEHAVIOR);
  }

  /**
   * Returns the behaviors initialized since the last call, and forgets them.
   */
  private function getInitializedBehaviors(): array {
    $response = CelerityAPI::getStaticResourceResponse()
      ->buildAjaxResponse(null);

    return idx($response, 'javelin_behaviors', []);
  }

}

<?php

/**
 * Adds "Hide Lint Inlines" to the inline comments menu on Differential
 * revisions.
 *
 * Revisions are the only pages that both show lint messages inline and render
 * an action list for their object: a standalone diff renders its properties
 * without the diff, and a Diffusion commit has no lint inlines.
 */
final class DifferentialHideLintInlinesUIEventListener
  extends PhabricatorAutoEventListener {

  public function register(): void {
    $this->listen(PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS);
  }

  public function handleEvent(PhutilEvent $event): void {
    switch ($event->getType()) {
      case PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS:
        $this->handleActionEvent($event);
        break;
    }
  }

  private function handleActionEvent(PhabricatorEvent $event): void {
    $object = $event->getValue('object');
    if (!($object instanceof DifferentialRevision)) {
      return;
    }

    // Core's translations for this menu are set on the PHP side, and `pht()`
    // in JavaScript throws in developer mode for any other string.
    Javelin::initBehavior(
      'differential-hide-lint-inlines',
      ['name' => pht('Hide Lint Inlines')],
      'phlab');
  }

}

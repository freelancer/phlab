<?php

final class PhabricatorCopyPHIDAction extends PhabricatorAutoEventListener {

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

  private function handleActionEvent($event): void {
    $viewer = $event->getUser();
    $object = $event->getValue('object');

    if (!$viewer->getIsAdmin()) {
      return;
    }

    if (!$object || !$object->getPHID()) {
      return;
    }

    Javelin::initBehavior('phabricator-clipboard-copy');
    $action = (new PhabricatorActionView())
      ->setHref('#')
      ->setRenderAsForm(true)
      ->setName(pht('Copy PHID'))
      ->setIcon('fa-clipboard')
      ->addSigil('clipboard-copy')
      ->setMetadata(['text' => $object->getPHID()]);
    $this->addActionMenuItems($event, $action);
  }

}

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

  private function handleActionEvent(PhabricatorEvent $event): void {
    $viewer = $event->getUser();
    $object = $event->getValue('object');

    if ($object === null || $object->getPHID() === null) {
      return;
    }

    $action = (new PhabricatorActionView())
      ->setViewer($viewer)
      ->initBehavior('phabricator-clipboard-copy')
      ->setHref('#')
      ->setRenderAsForm(true)
      ->setName(pht('Copy PHID'))
      ->setIcon('fa-clipboard')
      ->addSigil('clipboard-copy')
      ->setMetadata(['text' => $object->getPHID()])
      ->setHidden(!$viewer->getIsAdmin());

    $this->addActionMenuItems($event, $action);
  }

}

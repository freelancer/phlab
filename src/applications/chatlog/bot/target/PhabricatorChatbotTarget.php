<?php

/**
 * An entity that can be the target of messages, such as a user or channel.
 */
abstract class PhabricatorChatbotTarget extends Phobject {

  private $name;

  final public function getName() {
    return $this->name;
  }

  final public function setName($name) {
    $this->name = $name;
    return $this;
  }

  abstract public function isPublic();

}

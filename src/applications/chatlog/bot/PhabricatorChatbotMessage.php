<?php

final class PhabricatorChatbotMessage extends Phobject {

  private $body;
  private $sender;
  private $target;

  public function getBody() {
    return $this->body;
  }

  public function setBody($body) {
    $this->body = $body;
    return $this;
  }

  public function getSender() {
    return $this->sender;
  }

  public function setSender(PhabricatorChatbotTarget $sender = null) {
    $this->sender = $sender;
    return $this;
  }

  public function getTarget() {
    return $this->target;
  }

  public function setTarget(PhabricatorChatbotTarget $target = null) {
    $this->target = $target;
    return $this;
  }

}

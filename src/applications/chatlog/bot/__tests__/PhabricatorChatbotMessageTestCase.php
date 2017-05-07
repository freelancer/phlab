<?php

final class PhabricatorChatbotMessageTestCase extends PhutilTestCase {

  public function testBody() {
    $body = 'Message body';
    $message = new PhabricatorChatbotMessage();

    $message->setBody($body);
    $this->assertEqual($body, $message->getBody());
  }

  public function testSetBodyReturnsThis() {
    $message = new PhabricatorChatbotMessage();
    $this->assertEqual($message, $message->setBody(null));
  }

  public function testSender() {
    $sender = new PhabricatorChatbotUser();
    $message = new PhabricatorChatbotMessage();

    $message->setSender($sender);
    $this->assertEqual($sender, $message->getSender());
  }

  public function testSetSenderReturnsThis() {
    $message = new PhabricatorChatbotMessage();
    $this->assertEqual($message, $message->setSender(null));
  }

  public function testTarget() {
    $target = new PhabricatorChatbotChannel();
    $message = new PhabricatorChatbotMessage();

    $message->setTarget($target);
    $this->assertEqual($target, $message->getTarget());
  }

  public function testSetTargetReturnsThis() {
    $message = new PhabricatorChatbotMessage();
    $this->assertEqual($message, $message->setTarget(null));
  }

}

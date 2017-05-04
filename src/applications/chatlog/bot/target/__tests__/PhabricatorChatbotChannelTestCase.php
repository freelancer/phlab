<?php

final class PhabricatorChatbotChannelTestCase extends PhutilTestCase {

  public function testName() {
    $name = 'Engineering';
    $target = new PhabricatorChatbotChannel();

    $target->setName($name);
    $this->assertEqual($name, $target->getName());
  }

  public function testSetNameReturnsThis() {
    $target = new PhabricatorChatbotChannel();
    $this->assertEqual($target, $target->setName(null));
  }

  public function testIsPublic() {
    $target = new PhabricatorChatbotChannel();
    $this->assertTrue($target->isPublic());
  }

}

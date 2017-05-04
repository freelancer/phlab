<?php

final class PhabricatorChatbotUserTestCase extends PhutilTestCase {

  public function testName() {
    $name = 'John Smith';
    $target = new PhabricatorChatbotUser();

    $target->setName($name);
    $this->assertEqual($name, $target->getName());
  }

  public function testSetNameReturnsThis() {
    $target = new PhabricatorChatbotUser();
    $this->assertEqual($target, $target->setName(null));
  }

  public function testIsPublic() {
    $target = new PhabricatorChatbotUser();
    $this->assertFalse($target->isPublic());
  }

}

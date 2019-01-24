<?php

final class PhabricatorTriggerContentSourceTestCase extends PhutilTestCase {

  public function test(): void {
    $content_source = PhabricatorContentSource::newForSource(
      PhabricatorTriggerContentSource::SOURCECONST);
    $this->assertTrue(
      $content_source instanceof PhabricatorTriggerContentSource);
  }

}

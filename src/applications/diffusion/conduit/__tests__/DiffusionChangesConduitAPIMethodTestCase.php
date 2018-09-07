<?php

final class DiffusionChangesConduitAPIMethodTestCase extends PhutilTestCase {

  public function testIsValidCommitIdentifier() {
    $identifiers = [
      null                                         => false,
      '4b825dc6'                                   => false,
      '4b825dc642cb6eb9a060e54bf8d69288fbee4904'   => true,
      '4b825dc642cb6eb9a060e54bf8d69288fbee4904~'  => false,
      '4b825dc642cb6eb9a060e54bf8d69288fbee4904~2' => false,
      'HEAD'                                       => false,
      'master'                                     => false,
    ];

    foreach ($identifiers as $identifier => $should_be_valid) {
      $is_valid = DiffusionChangesConduitAPIMethod::isValidCommitIdentifier($identifier);

      if ($should_be_valid) {
        $this->assertTrue($is_valid);
      } else {
        $this->assertFalse($is_valid);
      }
    }
  }

}

<?php

final class PhlabPatchListTestCase extends PhutilTestCase {

  public function testPatches() {
    $patches = (new FileFinder(PhlabPatchList::getPatchDirectory()))
      ->withType('f')
      ->find();

    foreach ($patches as $file) {
      $this->assertPatchExists($file);
    }
  }

  private function assertPatchExists(string $name): void {
    $patches   = PhabricatorSQLPatchList::buildAllPatches();
    $namespace = (new PhlabPatchList())->getNamespace();

    $this->assertTrue(array_key_exists($namespace.':'.$name, $patches));
  }

}

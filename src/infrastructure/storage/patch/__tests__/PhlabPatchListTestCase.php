<?php

final class PhlabPatchListTestCase extends PhutilTestCase {

  public function testNamespace(): void {
    $this->assertEqual(
      phutil_get_current_library_name(),
      (new PhlabPatchList())->getNamespace());
  }

  public function testPatches(): void {
    $all_patches = PhabricatorSQLPatchList::buildAllPatches();
    $patch_list  = new PhlabPatchList();

    $namespace = $patch_list->getNamespace();
    $patches   = (new FileFinder($patch_list->getPatchDirectory()))
      ->withType('f')
      ->find();

    foreach ($patches as $patch) {
      $this->assertPatchExists($all_patches, $namespace, $patch);
    }
  }

  private function assertPatchExists(array $patches, string $namespace, string $patch): void {
    $this->assertTrue(array_key_exists($namespace.':'.$patch, $patches));
  }

}

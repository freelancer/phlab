<?php

final class PhlabPatchListTestCase extends PhutilTestCase {

  public function testPatches() {
    $this->assertPatchExists('20180119.herald.php');
    $this->assertPatchExists('20180510.maniphest.jiramigration.sql');
  }

  private function assertPatchExists(string $name): void {
    $patches   = PhabricatorSQLPatchList::buildAllPatches();
    $namespace = (new PhlabPatchList())->getNamespace();

    $this->assertTrue(array_key_exists($namespace.':'.$name, $patches));
  }

}

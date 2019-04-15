<?php

final class PhlabPatchList extends PhabricatorSQLPatchList {

  public function getNamespace(): string {
    return phutil_get_current_library_name();
  }

  public function getPatches(): array {
    $auto_root = $this->getPatchDirectory();
    $patches   = $this->buildPatchesFromDirectory($auto_root);

    // Phabricator requires that the first element of a patch list
    // has an `after` key.
    $patches[head_key($patches)]['after'] = [];

    return $patches;
  }

  public function getPatchDirectory(): string {
    $library = phutil_get_current_library_name();
    $root    = dirname(phutil_get_library_root($library));

    return $root.'/resources/sql/autopatches/';
  }
}

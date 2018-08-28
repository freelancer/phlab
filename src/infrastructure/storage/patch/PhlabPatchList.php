<?php

final class PhlabPatchList extends PhabricatorSQLPatchList {

  public function getNamespace(): string {
    return 'phlab';
  }

  public function getPatches(): array {
    $patches = $this->buildPatchesFromDirectory(self::getPatchDirectory());

    // Phabricator requires that the first element of a patch list
    // has an `after` key.
    reset($patches);
    $patches[key($patches)]['after'] = [];

    return $patches;
  }

  public static function getPatchDirectory(): string {
    $library = phutil_get_current_library_name();
    $root    = dirname(phutil_get_library_root($library));

    return $root.'/resources/sql/autopatches/';
  }
}

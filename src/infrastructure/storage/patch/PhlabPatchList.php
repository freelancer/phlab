<?php

final class PhlabPatchList extends PhabricatorSQLPatchList {

  public function getNamespace() {
    return 'phlab';
  }

  public function getPatches() {
    $root = dirname(phutil_get_library_root('phlab'));
    $patches = $this->buildPatchesFromDirectory(
      $root.'/resources/sql/autopatches/');

    // Phabricator requires that the first element of a patch list
    // has an `after` key.
    reset($patches);
    $patches[key($patches)]['after'] = [];

    return $patches;
  }
}
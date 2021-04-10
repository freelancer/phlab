<?php

final class PhabricatorBugCategorizationFieldValuePHIDType
extends PhabricatorPHIDType {

  const TYPECONST = 'BUGC';

  public function getTypeName() {
    return pht('BugCategorization');
  }

  public function getPHIDTypeApplicationClass() {
    return null;
  }

  protected function buildQueryForObjects(PhabricatorObjectQuery $query,
    array $phids) {

    return id(new PhabricatorBugCategorizationTokenQuery())
    ->withPHIDs($phids);
  }

  public function loadHandles(PhabricatorHandleQuery $query,
    array $handles, array $objects) {

    foreach ($handles as $phid => $handle) {
      $token = $objects[$phid];
      $name = $token->getName();
      $handle->setName(pht('bugcat %s', $name));
    }
  }
}

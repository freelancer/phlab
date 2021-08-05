<?php

abstract class PonderConduitAPIMethod extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass(
      'PhabricatorPonderApplication');
  }

}

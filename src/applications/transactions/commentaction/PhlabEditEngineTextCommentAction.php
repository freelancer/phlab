<?php

final class PhlabEditEngineTextCommentAction
  extends PhabricatorEditEngineCommentAction {

  public function getPHUIXControlType() {
    return 'text';
  }

  public function getPHUIXControlSpecification() {
    return [
      'value' => $this->getValue(),
    ];
  }

}

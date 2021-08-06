<?php

final class PonderSearchConduitAPIMethod
  extends PhabricatorSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'ponder.search';
  }

  public function newSearchEngine() {
    return new PonderQuestionSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Search a question on ponder.');
  }

}

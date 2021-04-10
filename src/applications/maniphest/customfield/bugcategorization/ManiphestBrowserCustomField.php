<?php

final class ManiphestBrowserCustomField
extends ManiphestMultiValueCustomField {

  // from PhabricatorStandardCustomField
  public function getFieldType() {
    return 'bugcategorization-browser';
  }

  // from ManiphestMultiValueCustomField
  public function getDatasourceClassName() {
    return 'ManiphestBrowserDatasource';
  }

  public function getClassFieldKey() {
    return 'maniphest:bug-categorization:browser';
  }

  public function getClassFieldName() {
    return 'Browsers';
  }

  public function getClassFieldDescription() {
    return 'Browsers from where the bug was found';
  }
}

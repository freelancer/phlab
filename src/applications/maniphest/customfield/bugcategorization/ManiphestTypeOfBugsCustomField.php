<?php

final class ManiphestTypeOfBugsCustomField
extends ManiphestMultiValueCustomField {

  // from PhabricatorStandardCustomField
  public function getFieldType() {
    return 'bugcategorization-type';
  }

  // from ManiphestMultiValueCustomField
  public function getDatasourceClassName() {
    return 'ManiphestTypeOfBugsDatasource';
  }

  public function getClassFieldKey() {
    return 'maniphest:bug-categorization:type-of-bugs';
  }

  public function getClassFieldName() {
    return 'Types of Bug';
  }

  public function getClassFieldDescription() {
    return 'Classifications assigned to the bug';
  }
}

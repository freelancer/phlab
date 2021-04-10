<?php

final class ManiphestRootCauseCustomField
extends ManiphestMultiValueCustomField {

  // from PhabricatorStandardCustomField
  public function getFieldType() {
    return 'bugcategorization-rootcause';
  }

  // from ManiphestMultiValueCustomField
  public function getDatasourceClassName() {
    return 'ManiphestRootCauseDatasource';
  }

  public function getClassFieldKey() {
    return 'maniphest:bug-categorization:root-cause';
  }

  public function getClassFieldName() {
    return 'Root Causes';
  }

  public function getClassFieldDescription() {
    return 'Possible reasons or causes leading to the bug';
  }
}

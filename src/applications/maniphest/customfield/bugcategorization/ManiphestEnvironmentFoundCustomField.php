<?php

final class ManiphestEnvironmentFoundCustomField
extends ManiphestMultiValueCustomField {

  // from PhabricatorStandardCustomField
  public function getFieldType() {
    return 'bugcategorization-environment';
  }

  // from ManiphestMultiValueCustomField
  public function getDatasourceClassName() {
    return 'ManiphestEnvironmentFoundDatasource';
  }

  public function getClassFieldKey() {
    return 'maniphest:bug-categorization:environment-found';
  }

  public function getClassFieldName() {
    return 'Environments Found';
  }

  public function getClassFieldDescription() {
    return 'Environments where the bug had been found';
  }
}

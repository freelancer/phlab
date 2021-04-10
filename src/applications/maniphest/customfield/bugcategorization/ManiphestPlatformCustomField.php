<?php

final class ManiphestPlatformCustomField
extends ManiphestMultiValueCustomField {

  // from PhabricatorStandardCustomField
  public function getFieldType() {
    return 'bugcategorization-platform';
  }

  // from ManiphestMultiValueCustomField
  public function getDatasourceClassName() {
    return 'ManiphestPlatformDatasource';
  }

  public function getClassFieldKey() {
    return 'maniphest:bug-categorization:platform';
  }

  public function getClassFieldName() {
    return 'Platforms';
  }

  public function getClassFieldDescription() {
    return 'Platforms in which the bug was found';
  }
}

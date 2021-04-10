<?php

final class ManiphestBugReporterCustomField
extends ManiphestMultiValueCustomField {

  // from PhabricatorStandardCustomField
  public function getFieldType() {
    return 'bugcategorization-reporter';
  }

  // from ManiphestMultiValueCustomField
  public function getDatasourceClassName() {
    return 'ManiphestBugReporterDatasource';
  }

  public function getClassFieldKey() {
    return 'maniphest:bug-categorization:bug-reporter';
  }

  public function getClassFieldName() {
    return 'Bug Reporters';
  }

  public function getClassFieldDescription() {
    return 'Groups who identified the bug';
  }
}

<?php

final class ManiphestBugReporterDatasource
extends ManiphestStaticMapDatasource {

  public function __construct() {
    // note: make sure any changes to keys are reflected in
    // PhabricatorBugCategorizationTokenQuery::getBuiltinTokens
    $this->setDataMapping([
      'bug_reporter_automated_tests' => [
        'display' => 'Automated tests',
      ],
      'bug_reporter_internal_staff' => [
        'display' => 'Internal staff',
      ],
      'bug_reporter_qa' => [
        'display' => 'QA',
      ],
      'bug_reporter_users' => [
        'display' => 'Users',
      ],
      ]);

    $this->setBrowseTitle(pht('Bug Reporters'));
    $this->setPlaceholderText(
      pht('Enter the entity (or entities) that discovered this bug'));
    $this->setIcon('fa-users');
    $this->setColor('orange');
  }

  public function getGenericResultDescription(): string {
    return 'Bug Reporter';
  }
}

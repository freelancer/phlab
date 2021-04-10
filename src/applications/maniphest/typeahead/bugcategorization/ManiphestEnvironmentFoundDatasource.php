<?php

final class ManiphestEnvironmentFoundDatasource
extends ManiphestStaticMapDatasource {

  public function __construct() {
    // note: make sure any changes to keys are reflected in
    // PhabricatorBugCategorizationTokenQuery::getBuiltinTokens
    $this->setDataMapping([
      'environment_all' => ['display' => 'All'],
      'environment_development' => ['display' => 'Development'],
      'environment_sandbox' => ['display' => 'Sandbox'],
      'environment_staging' => ['display' => 'Staging'],
      'environment_production' => ['display' => 'Production'],
      ]);

    $this->setBrowseTitle(pht('Environments'));
    $this->setPlaceholderText(
      pht('Enter the environment(s) from where the bug was found'));
    $this->setIcon('fa-database');
    $this->setColor('indigo');
  }

  public function getGenericResultDescription(): string {
    return 'Environment';
  }
}

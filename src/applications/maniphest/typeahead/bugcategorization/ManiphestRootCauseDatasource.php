<?php

final class ManiphestRootCauseDatasource extends ManiphestStaticMapDatasource {

  public function __construct() {
    // note: make sure any changes to keys are reflected in
    // PhabricatorBugCategorizationTokenQuery::getBuiltinTokens
    $this->setDataMapping([
      'root_cause_api_usage' => [
        'display' => 'API Usage',
      ],
      'root_cause_backend_business_logic' => [
        'display' => 'Backend Business Logic',
      ],
      'root_cause_backend_type_issue' => [
        'display' => 'Backend Type Issue',
      ],
      'root_cause_business_logic' => [
        'display' => 'Business Logic',
      ],
      'root_cause_database_sql_queries' => [
        'display' => 'Database/SQL Queries',
      ],
      'root_cause_design_specification' => [
        'display' => 'Design Specification',
      ],
      'root_cause_frontend' => [
        'display' => 'Frontend',
      ],
      'root_cause_frontend_configuration' => [
        'display' => 'Frontend Configuration',
      ],
      'root_cause_infrastructure_configuration' => [
        'display' => 'Infrastructure Configuration',
      ],
      'root_cause_infrastructure_failure' => [
        'display' => 'Infrastructure Failure',
      ],
      'root_cause_operational_error' => [
        'display' => 'Operational Error',
      ],
      'root_cause_product_specification' => [
        'display' => 'Product Specification',
      ],
      'root_cause_test_script_issue' => [
        'display' => 'Test Script Issue',
      ],
      'root_cause_third_party_dependency_failure' => [
        'display' => 'Third Party Dependency Failure',
      ],
      'root_cause_third_party_integration' => [
        'display' => 'Third Party Integration',
      ],
      ]);

    $this->setBrowseTitle(pht('Root Causes'));
    $this->setPlaceholderText(
      pht('Enter identified root cause(s) for this bug'));
    $this->setIcon('fa-bomb');
    $this->setColor('red');
  }

  public function getGenericResultDescription(): string {
    return 'Root Cause';
  }
}

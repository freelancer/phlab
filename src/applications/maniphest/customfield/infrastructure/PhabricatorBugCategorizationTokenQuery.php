<?php

final class PhabricatorBugCategorizationTokenQuery
extends PhabricatorCursorPagedPolicyAwareQuery {

  private $phids;

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  protected function loadPage() {
    $tokens = $this->getBuiltinTokens();
    if ($this->phids) {
      $map = array_fill_keys($this->phids, true);
      foreach ($tokens as $key => $token) {
        if (empty($map[$token->getPHID()])) {
          unset($tokens[$key]);
        }
      }
    }
    return $tokens;
  }

  private function getBuiltinTokens() {

    $names = array(
      'browser_chrome',
      'browser_firefox',
      'browser_ie',
      'browser_others',
      'browser_safari',

      'bug_reporter_automated_tests',
      'bug_reporter_internal_staff',
      'bug_reporter_qa',
      'bug_reporter_users',

      'environment_all',
      'environment_development',
      'environment_sandbox',
      'environment_staging',
      'environment_production',

      'platform_android',
      'platform_desktop_app',
      'platform_ios',
      'platform_linux',
      'platform_mac',
      'platform_others',
      'platform_windows',

      'root_cause_api_usage',
      'root_cause_backend_business_logic',
      'root_cause_backend_type_issue',
      'root_cause_business_logic',
      'root_cause_database_sql_queries',
      'root_cause_design_specification',
      'root_cause_frontend',
      'root_cause_frontend_configuration',
      'root_cause_infrastructure_configuration',
      'root_cause_infrastructure_failure',
      'root_cause_operational_error',
      'root_cause_product_specification',
      'root_cause_test_script_issue',
      'root_cause_third_party_dependency_failure',
      'root_cause_third_party_integration',

      'type_of_bug_bad_ux',
      'type_of_bug_browser_compatibility',
      'type_of_bug_error_handling',
      'type_of_bug_functionality',
      'type_of_bug_localization',
      'type_of_bug_performance',
      'type_of_bug_ui',
    );

    $type = PhabricatorBugCategorizationFieldValuePHIDType::TYPECONST;

    $tokens = array();
    foreach ($names as $name) {
      $token = id(new PhabricatorToken())
        ->setID($name)
        ->setName($name)
        ->setPHID('PHID-'.$type.'-'.$name);
      $tokens[] = $token;
    }
    return $tokens;
  }

  public function getQueryApplicationClass() {
    return null;
  }
}

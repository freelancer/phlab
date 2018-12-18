<?php

final class PhabricatorViewPolicyHeraldActionTestCase extends PhutilTestCase {

  public function testSupportsObject(): void {
    $action = new PhabricatorViewPolicyHeraldAction();
    $task   = new ManiphestTask();

    $this->assertTrue($action->supportsObject($task));
  }

  public function testSupportsRuleType(): void {
    $action = new PhabricatorViewPolicyHeraldAction();

    $rule_types = [
      HeraldRuleTypeConfig::RULE_TYPE_GLOBAL   => true,
      HeraldRuleTypeConfig::RULE_TYPE_OBJECT   => false,
      HeraldRuleTypeConfig::RULE_TYPE_PERSONAL => false,
    ];

    foreach ($rule_types as $rule_type => $supported) {
      if ($supported) {
        $this->assertTrue($action->supportsRuleType($rule_type));
      } else {
        $this->assertFalse($action->supportsRuleType($rule_type));
      }
    }
  }

  public function testWillSaveActionValue(): void {
    $this->tryTestCases(
      [
        'public'  => PhabricatorPolicies::POLICY_PUBLIC,
        'user'    => PhabricatorPolicies::POLICY_USER,
        'admin'   => PhabricatorPolicies::POLICY_ADMIN,
        'no-one'  => PhabricatorPolicies::POLICY_NOONE,
        'object'  => 'obj.subscriptions.subscribers',
        'invalid' => 'derp',
      ],
      [
        true,
        true,
        true,
        true,
        true,
        false,
      ],
      function (string $value): void {
        $action = new PhabricatorViewPolicyHeraldAction();
        $action->willSaveActionValue($value);
      });
  }

}

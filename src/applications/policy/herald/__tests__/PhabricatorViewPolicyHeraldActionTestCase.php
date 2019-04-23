<?php

final class PhabricatorViewPolicyHeraldActionTestCase extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testSupportsObject(): void {
    $action = new PhabricatorViewPolicyHeraldAction();

    $this->assertFalse($action->supportsObject(new stdClass()));
    $this->assertTrue($action->supportsObject(new ManiphestTask()));
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

  public function testApplyEffect(): void {
    $user = $this->generateNewTestUser();
    $task = ManiphestTask::initializeNewTask($user);

    $adapter = (new HeraldManiphestTaskAdapter())
      ->setObject($task);

    $record = (new HeraldActionRecord())
      ->setAction(PhabricatorViewPolicyHeraldAction::ACTIONCONST)
      ->setTarget(PhabricatorPolicies::POLICY_NOONE);

    $condition = (new HeraldCondition())
      ->setFieldName(HeraldAlwaysField::FIELDCONST)
      ->setFieldCondition(HeraldAdapter::CONDITION_UNCONDITIONALLY);

    $rule = (new HeraldRule())
      ->setName('Test')
      ->setAuthorPHID($user->getPHID())
      ->setContentType($adapter->getAdapterContentType())
      ->setMustMatchAll(true)
      ->setRepetitionPolicy(HeraldRule::REPEAT_EVERY)
      ->setRuleType(HeraldRuleTypeConfig::RULE_TYPE_GLOBAL)
      ->attachConditions([$condition])
      ->attachActions([$record])
      ->attachValidAuthor(true)
      ->save();

    $effect = (new HeraldEffect())
      ->setObjectPHID($task->getPHID())
      ->setAction($record)
      ->setTarget($record->getTarget())
      ->setRule($rule);

    $engine = new HeraldEngine();
    $effects = $engine->applyRules([$rule], $adapter);
    $engine->applyEffects($effects, $adapter, [$rule]);

    (new ManiphestTransactionEditor())
      ->setContinueOnNoEffect(true)
      ->setContinueOnMissingFields(true)
      ->setIsHeraldEditor(true)
      ->setActor(PhabricatorUser::getOmnipotentUser())
      ->setActingAsPHID((new PhabricatorHeraldApplication())->getPHID())
      ->setContentSource(PhabricatorContentSource::newForSource(
        PhabricatorHeraldContentSource::SOURCECONST))
      ->applyTransactions($task, $adapter->getQueuedTransactions());

    $this->assertEqual($record->getTarget(), $task->getViewPolicy());
  }

  public function testWillSaveActionValue(): void {
    $object_policy_key = function (PhabricatorPolicyRule $policy_rule): string {
      $prefix = PhabricatorPolicyQuery::OBJECT_POLICY_PREFIX;
      $key = $policy_rule->getObjectPolicyKey();

      return $prefix.$key;
    };

    $this->tryTestCases(
      [
        'public'  => PhabricatorPolicies::POLICY_PUBLIC,
        'user'    => PhabricatorPolicies::POLICY_USER,
        'admin'   => PhabricatorPolicies::POLICY_ADMIN,
        'no-one'  => PhabricatorPolicies::POLICY_NOONE,
        'object'  => $object_policy_key(new PhabricatorSubscriptionsSubscribersPolicyRule()),
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

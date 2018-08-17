<?php

/**
 * TODO: Remove this after T21277. We probably won't need to create policies
 * via Conduit after we have migrated repository hosting.
 */
final class CreatePolicyConduitAPIMethod extends ConduitAPIMethod {

  public function getAPIMethodName(): string {
    return 'policy.create';
  }

  public function getMethodDescription(): string {
    return pht('Create custom access control policy objects.');
  }

  protected function defineParamTypes(): array {
    return [
      'objectType' => 'required string',
      'default'    => 'required string',
      'policy'     => 'required list<map<string, wild>>',
    ];
  }

  protected function defineReturnType(): string {
    return 'map<string, string>';
  }

  protected function defineErrorTypes(): array {
    return [
      'ERR-INVALID-PARAMETER' => pht('Missing or malformed parameter.'),
    ];
  }

  protected function execute(ConduitAPIRequest $request): array {
    $actor = $request->getUser();

    $action_options = [
      PhabricatorPolicy::ACTION_ALLOW => pht('Allow'),
      PhabricatorPolicy::ACTION_DENY  => pht('Deny'),
    ];

    $object_type = $request->getValue('objectType');
    if ($object_type) {
      $phid_types = PhabricatorPHIDType::getAllInstalledTypes($request->getUser());
      if (empty($phid_types[$object_type])) {
        return new Aphront404Response();
      }

      $object = $phid_types[$object_type]->newObject();
      if (!$object) {
        throw (new ConduitException('ERR-INVALID-PARAMETER'))
          ->setErrorDescription(pht("Unknown object type: '%s'", $object_type));
      }
    } else {
      throw (new ConduitException('ERR-INVALID-PARAMETER'))
        ->setErrorDescription(pht('Object type is required.'));
    }

    $rules = (new PhutilClassMapQuery())
      ->setAncestorClass(PhabricatorPolicyRule::class)
      ->execute();

    foreach ($rules as $key => $rule) {
      if (!$rule->canApplyToObject($object)) {
        unset($rules[$key]);
      }
    }

    $rules = msort($rules, 'getRuleOrder');

    $default_rule = [
      'action' => head_key($action_options),
      'rule'   => head_key($rules),
      'value'  => null,
    ];

    $policy = (new PhabricatorPolicy())
      ->setRules([$default_rule])
      ->setDefaultAction(PhabricatorPolicy::ACTION_DENY);

    $root_id        = celerity_generate_unique_node_id();
    $default_action = $policy->getDefaultAction();
    $rule_data      = $policy->getRules();
    $data           = $request->getValue('policy');
    $rule_data      = array();

    foreach ($data as $rule) {
      $action = idx($rule, 'action');

      switch ($action) {
        case 'allow':
        case 'deny':
          break;
        default:
          throw (new ConduitException('ERR-INVALID-PARAMETER'))
            ->setErrorDescription(pht("Invalid action: '%s'!", $action));
      }

      $rule_class = idx($rule, 'rule');
      if (empty($rules[$rule_class])) {
        throw (new ConduitException('ERR-INVALID-PARAMETER'))
          ->setErrorDescription(pht("Invalid rule class: '%s'!", $rule_class));
      }

      $rule_obj = $rules[$rule_class];
      $value    = $rule_obj->getValueForStorage(idx($rule, 'value'));

      $rule_data[] = [
        'action' => $action,
        'rule'   => $rule_class,
        'value'  => $value,
      ];
    }

    // Filter out nonsense rules, like a "users" rule without any users
    // actually specified.
    $valid_rules = array();
    foreach ($rule_data as $rule) {
      $rule_class = $rule['rule'];
      if ($rules[$rule_class]->ruleHasEffect($rule['value'])) {
        $valid_rules[] = $rule;
      }
    }

    if (!$valid_rules) {
      throw (new ConduitException('ERR-INVALID-PARAMETER'))
        ->setErrorDescription(pht('Rules do not have any effect.'));
    }

    // NOTE: Policies are immutable once created, and we always create a new
    // policy here. If we didn't, we would need to lock this endpoint down,
    // as users could otherwise just go edit the policies of objects with
    // custom policies.
    $data = [];

    $new_policy = (new PhabricatorPolicy())
      ->setRules($valid_rules)
      ->setDefaultAction($request->getValue('default'))
      ->save();

    return [
      'phid' => $new_policy->getPHID(),
      'name' => $new_policy->getName(),
      'full' => $new_policy->getName(),
      'icon' => $new_policy->getIcon(),
    ];
  }

}

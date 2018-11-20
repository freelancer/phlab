<?php

final class PhabricatorViewPolicyHeraldAction extends HeraldAction {

  const ACTIONCONST = 'policy.view';
  const DO_POLICY   = 'do.policy';

  public function getHeraldActionName(): string {
    return pht('Set view policy');
  }

  public function supportsObject($object): bool {
    if (!$object instanceof PhabricatorPolicyInterface) {
      return false;
    }

    return in_array(
      PhabricatorPolicyCapability::CAN_VIEW,
      $object->getCapabilities());
  }

  public function supportsRuleType($rule_type): bool {
    return $rule_type === HeraldRuleTypeConfig::RULE_TYPE_GLOBAL;
  }

  public function applyEffect($object, HeraldEffect $effect): void {
    $adapter = $this->getAdapter();
    $target  = $effect->getTarget();

    if (!$target) {
      $this->logEffect(self::DO_STANDARD_EMPTY);
      return;
    }

    $current_policy = $this->getPolicy($object->getViewPolicy());
    $target_policy  = $this->getPolicy($target);

    // TODO: This doesn't seem to work. The intention is that this Herald
    // action shouldn't ever change the view policy to be //less// restrictive.
    if ($current_policy->isStrongerThan($target_policy)) {
      $this->logEffect(self::DO_STANDARD_NO_EFFECT, $target);
      return;
    }

    $xaction = $adapter->newTransaction()
      ->setTransactionType(PhabricatorTransactions::TYPE_VIEW_POLICY)
      ->setNewValue($target);

    $adapter->queueTransaction($xaction);
    $this->logEffect(self::DO_POLICY, $target);
  }

  public function renderActionDescription($value): PhutilSafeHTML {
    return pht('Set view policy: %s', $this->renderPolicy($value));
  }

  protected function renderActionEffectDescription($type, $data): PhutilSafeHTML {
    switch ($type) {
      case self::DO_POLICY:
        return pht('Set view policy to %s.', $this->renderPolicy($data));
    }
  }

  public function getActionGroupKey(): string {
    return HeraldSupportActionGroup::ACTIONGROUPKEY;
  }

  public function getHeraldActionStandardType(): string {
    // TODO: Ideally we would allow the target policy to be constructed using
    // the UI, but Herald doesn't support this at the moment.
    return self::STANDARD_TEXT;
  }

  public function willSaveActionValue($value) {
    if (PhabricatorPolicyQuery::isGlobalPolicy($value)) {
      return parent::willSaveActionValue($value);
    }

    if (phid_get_type($value) !== PhabricatorPolicyPHIDTypePolicy::TYPECONST) {
      throw new HeraldInvalidActionException(
        pht('Invalid policy identifier: %s', $value));
    }

    if ($this->getPolicy($value) === null) {
      throw new HeraldInvalidActionException(
        pht('Invalid policy identifier: %s', $value));
    }

    return parent::willSaveActionValue($value);
  }

  protected function getActionEffectMap(): array {
    return [
      self::DO_POLICY => [
        'icon'  => 'fa-eye',
        'color' => 'green',
        'name'  => pht('Changed view policy'),
      ],
    ];
  }

  private function getPolicy(string $phid): ?PhabricatorPolicy {
    // TODO: We should use `$this->getViewer()`, but it doesn't seem to
    // actually be set.
    $viewer = coalesce(
      $this->getViewer(),
      PhabricatorUser::getOmnipotentUser());

    return (new PhabricatorPolicyQuery())
      ->setViewer($viewer)
      ->withPHIDs([$phid])
      ->executeOne();
  }

  private function renderPolicy(string $value): PhutilSafeHTML {
    switch ($value) {
      case PhabricatorPolicies::POLICY_ADMIN:
      case PhabricatorPolicies::POLICY_NOONE:
      case PhabricatorPolicies::POLICY_PUBLIC:
      case PhabricatorPolicies::POLICY_USER:
        return new PhutilSafeHTML($value);

      default:
        return $this->renderHandleList([$value]);
    }
  }

}

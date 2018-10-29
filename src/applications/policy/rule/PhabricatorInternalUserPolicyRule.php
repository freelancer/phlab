<?php

final class PhabricatorInternalUserPolicyRule extends PhabricatorPolicyRule {

  public function getRuleDescription(): string {
    return pht('internal users');
  }

  public function applyRule(PhabricatorUser $viewer, $value, PhabricatorPolicyInterface $object): bool {
    if (PhabricatorUserEmail::isEmailVerificationRequired() && !$viewer->getIsEmailVerified()) {
      return false;
    }

    return PhabricatorUserEmail::isAllowedAddress($viewer->loadPrimaryEmailAddress());
  }

  public function getValueControlType(): string {
    return self::CONTROL_TYPE_NONE;
  }

  public function getRuleOrder(): int {
    return 700;
  }

}

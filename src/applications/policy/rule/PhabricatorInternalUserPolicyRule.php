<?php

/**
 * A policy rule that checks whether a user's primary email address is in the
 * list of allowed domains.
 *
 * Normally a user wouldn't be able to register an account with an email domain
 * not whitelisted in `auth.email-domains`, but we intentionally bypass this
 * requirement in @{class:PhabricatorPeopleCreateWorkflow}.
 */
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

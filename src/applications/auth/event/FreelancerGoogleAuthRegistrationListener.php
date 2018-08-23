<?php

/**
 * An event listener which prevents users from changing their account details.
 *
 * When users attempt to login with Google for the very first time, they are
 * able to choose their own username. This is confusing and historically has
 * caused us to end up with duplicate Phabricator accounts (see T27264).
 * Instead of allowing users to choose their own username, automatically
 * register the user with the username provided by the
 * @{class:PhabricatorAuthProvider}. See https://secure.phabricator.com/T10700
 * for some related discussion upstream.
 */
final class FreelancerGoogleAuthRegistrationListener
  extends PhabricatorAutoEventListener {

  public function register(): void {
    $this->listen(PhabricatorEventType::TYPE_AUTH_WILLREGISTERUSER);
  }

  public function handleEvent(PhutilEvent $event): void {
    $account = $event->getValue('account');
    $profile = $event->getValue('profile');

    if ($account->getAccountType() !== 'google') {
      return;
    }

    $profile->setDefaultUserName($account->getUsername());
    $profile->setDefaultEmail($account->getEmail());
    $profile->setDefaultRealName($account->getRealName());

    $profile->setCanEditUsername(false);
    $profile->setCanEditEmail(false);
    $profile->setCanEditRealName(false);
    $profile->setShouldVerifyEmail(false);
  }

}

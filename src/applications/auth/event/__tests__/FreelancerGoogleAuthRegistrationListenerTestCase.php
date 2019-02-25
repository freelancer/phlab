<?php

final class FreelancerGoogleAuthRegistrationListenerTestCase
  extends PhabricatorTestCase {

  public function testHandleEvent(): void {
    $listener = new FreelancerGoogleAuthRegistrationListener();

    $env = PhabricatorEnv::beginScopedEnv();
    $env->overrideEnvConfig('phabricator.base-uri', 'http://phabricator.example.com');

    $provider_config = new PhabricatorAuthProviderConfig();
    $provider = (new PhabricatorGoogleAuthProvider())
      ->attachProviderConfig($provider_config);

    $username  = 'jsmith';
    $real_name = 'John Smith';
    $email     = 'john@example.com';

    $account = $provider->newDefaultExternalAccount()
      ->setUsername($username)
      ->setRealName($real_name)
      ->setEmail($email);
    $profile = new PhabricatorRegistrationProfile();

    $event = new PhabricatorEvent(
      PhabricatorEventType::TYPE_AUTH_WILLREGISTERUSER,
      [
        'account' => $account,
        'profile' => $profile,
      ]);
    $listener->handleEvent($event);

    $this->assertFalse($profile->getShouldVerifyEmail());
    $this->assertFalse($profile->getCanEditEmail());
    $this->assertFalse($profile->getCanEditRealName());
    $this->assertFalse($profile->getCanEditUsername());
    $this->assertEqual($email, $profile->getDefaultEmail());
    $this->assertEqual($real_name, $profile->getDefaultRealName());
    $this->assertEqual($username, $profile->getDefaultUserName());
  }

}

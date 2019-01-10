<?php

final class PhabricatorInternalUserPolicyRuleTestCase extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testApplyRule(): void {
    $internal_user = $this->generateNewTestUser();
    $internal_user->loadPrimaryEmail()
      ->setAddress('bob@internal.com')
      ->save();

    $external_user = $this->generateNewTestUser();
    $external_user->loadPrimaryEmail()
      ->setAddress('bob@external.com')
      ->save();

    $policy = (new PhabricatorPolicy())
      ->setRules([
        [
          'action' => PhabricatorPolicy::ACTION_ALLOW,
          'rule'   => PhabricatorInternalUserPolicyRule::class,
          'value'  => true,
        ],
      ])
      ->save();

    $author = $this->generateNewTestUser();
    $task = ManiphestTask::initializeNewTask($author)
      ->setViewPolicy($policy->getPHID())
      ->save();

    $env = PhabricatorEnv::beginScopedEnv();
    $env->overrideEnvConfig('auth.email-domains', ['internal.com']);

    $this->assertTrue($this->canView($internal_user, $task));
    $this->assertFalse($this->canView($external_user, $task));
  }

  private function canView(PhabricatorUser $user, PhabricatorPolicyInterface $object): bool {
    $capability = PhabricatorPolicyCapability::CAN_VIEW;
    return PhabricatorPolicyFilter::hasCapability($user, $object, $capability);
  }

}

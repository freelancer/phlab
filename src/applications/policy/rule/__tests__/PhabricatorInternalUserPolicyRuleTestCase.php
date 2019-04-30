<?php

final class PhabricatorInternalUserPolicyRuleTestCase extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testApplyRule(): void {
    $internal_domain = 'internal.com';
    $external_domain = 'external.com';

    $internal_user = $this->generateNewTestUser();
    $internal_user->loadPrimaryEmail()
      ->setAddress('bob@'.$internal_domain)
      ->save();

    $external_user = $this->generateNewTestUser();
    $external_user->loadPrimaryEmail()
      ->setAddress('bob@'.$external_domain)
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
    $env->overrideEnvConfig('auth.email-domains', [$internal_domain]);

    $this->assertTrue($this->canView($internal_user, $task));
    $this->assertFalse($this->canView($external_user, $task));
  }

  private function canView(PhabricatorUser $viewer, PhabricatorPolicyInterface $object): bool {
    return PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $object,
      PhabricatorPolicyCapability::CAN_VIEW);
  }

}

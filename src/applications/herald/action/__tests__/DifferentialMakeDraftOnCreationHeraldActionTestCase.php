<?php

final class DifferentialMakeDraftHeraldActionTestCase
  extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testIsValidEffect(): void {
    $user = $this->generateNewTestUser();
    $revision = $this->generateNewTestRevision($user);
    $herald_action = new DifferentialMakeDraftHeraldAction();
    $herald_action->setAdapter(new HeraldDifferentialRevisionAdapter());

    // new revision is not a valid case
    $this->assertFalse($herald_action->isValidEffect($revision));

    $params = array();
    $params['transactions'] = [
      ['type' => 'request-review', 'value' => true],
    ];
    $params['objectIdentifier'] = $revision->getPHID();

    $call = new ConduitCall('differential.revision.edit', $params, true);
    try {
      $call
        ->setUser($user)
        ->execute();
    } catch (Error $e) {
      // for some reason, the revision isn't attached to a diff which leads to an error
      // `(Error) Call to a member function getPHID() on null`
      // however, we don't care about that since the `differential.revision.request`
      // transaction is written at that point so we just proceed
    }

    // request review should make it valid
    $this->assertTrue($herald_action->isValidEffect($revision));

    // kill open transactions and locks
    $revision->killTransaction();
    $revision->endReadLocking();
  }

  private function generateNewTestRevision(PhabricatorUser $actor): DifferentialRevision {
    $diff = DifferentialDiff::initializeNewDiff($actor)
      ->setLintStatus(DifferentialLintStatus::LINT_AUTO_SKIP)
      ->setUnitStatus(DifferentialUnitStatus::UNIT_AUTO_SKIP)
      ->setLineCount(0)
      ->save();

    $revision = DifferentialRevision::initializeNewRevision($actor)
      ->setActiveDiffPHID($diff->getPHID())
      ->save();

    return $revision;
  }
}

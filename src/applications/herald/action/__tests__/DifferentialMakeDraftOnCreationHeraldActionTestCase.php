<?php

final class DifferentialMakeDraftOnCreationHeraldActionTestCase
  extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testIsValidEffect(): void {
    $user = $this->generateNewTestUser();
    $revision = $this->generateNewTestRevision($user);
    $herald_action = new DifferentialMakeDraftOnCreationHeraldAction();
    $herald_action->setAdapter(new HeraldDifferentialRevisionAdapter());

    // new revision is not a valid case
    $this->assertFalse($herald_action->isValidEffect($revision));

    // request review should make it valid
    $this->saveRequestReviewTransaction($user, $revision);
    $this->assertTrue($herald_action->isValidEffect($revision));
  }

  /**
   * Store the transaction that requesting review leaves on a revision, which
   * is all `isValidEffect()` reads. Applying it through the editor needs a
   * revision with a complete diff, and fails differently across Phabricator
   * versions without one.
   */
  private function saveRequestReviewTransaction(
    PhabricatorUser $actor,
    DifferentialRevision $revision): void {

    id(new DifferentialTransaction())
      ->setObjectPHID($revision->getPHID())
      ->setAuthorPHID($actor->getPHID())
      ->setViewPolicy(PhabricatorPolicies::getMostOpenPolicy())
      ->setEditPolicy($actor->getPHID())
      ->setTransactionType(
        DifferentialRevisionRequestReviewTransaction::TRANSACTIONTYPE)
      ->setOldValue(false)
      ->setNewValue(true)
      ->setContentSource($this->newContentSource())
      ->save();
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

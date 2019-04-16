<?php

final class DiffusionCommitRevisionResigningReviewersHeraldFieldTestCase
  extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testGetHeraldFieldValueWithNoRevision(): void {
    $author = $this->generateNewTestUser();
    $commit = $this->generateNewTestCommit($author);

    $adapter = (new HeraldCommitAdapter())
      ->setObject($commit);

    $field = (new DiffusionCommitRevisionResigningReviewersHeraldField())
      ->setAdapter($adapter);

    $this->assertEqual(
      [],
      $field->getHeraldFieldValue($commit));
  }

  public function testGetHeraldFieldValueWithRevision(): void {
    $author     = $this->generateNewTestUser();
    $reviewer_a = $this->generateNewTestUser();
    $reviewer_b = $this->generateNewTestUser();

    $revision = $this->generateNewTestRevision($author);
    $revision->attachReviewers([
      (new DifferentialReviewer())
        ->setRevisionPHID($revision->getPHID())
        ->setReviewerPHID($reviewer_a->getPHID())
        ->setReviewerStatus(DifferentialReviewerStatus::STATUS_ACCEPTED)
        ->save(),
      (new DifferentialReviewer())
        ->setRevisionPHID($revision->getPHID())
        ->setReviewerPHID($reviewer_b->getPHID())
        ->setReviewerStatus(DifferentialReviewerStatus::STATUS_RESIGNED)
        ->save(),
    ]);

    $commit = $this->generateNewTestCommit($author);
    $commit->getCommitData()->setCommitDetail('differential.revisionID', $revision->getID());

    $adapter = (new HeraldCommitAdapter())
      ->setObject($commit);

    $field = (new DiffusionCommitRevisionResigningReviewersHeraldField())
      ->setAdapter($adapter);

    $this->assertEqual(
      array_fuse([$reviewer_b->getPHID()]),
      $field->getHeraldFieldValue($commit));
  }

  private function generateNewTestCommit(PhabricatorUser $actor): PhabricatorRepositoryCommit {
    $repo = PhabricatorRepository::initializeNewRepository($actor)
      ->setName('Test')
      ->setVersionControlSystem(PhabricatorRepositoryType::REPOSITORY_TYPE_GIT)
      ->save();

    $commit = (new PhabricatorRepositoryCommit())
      ->setRepositoryID($repo->getID())
      ->setCommitIdentifier('4b825dc642cb6eb9a060e54bf8d69288fbee4904')
      ->setEpoch(PhabricatorTime::getNow())
      ->save();

    $commit_data = (new PhabricatorRepositoryCommitData())
      ->setCommitID($commit->getID())
      ->save();

    $commit->attachCommitData($commit_data);

    return $commit;
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

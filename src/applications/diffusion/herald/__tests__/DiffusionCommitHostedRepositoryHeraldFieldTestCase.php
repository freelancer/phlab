<?php

final class DiffusionCommitHostedRepositoryHeraldFieldTestCase
  extends PhutilTestCase {

  public function testGetHeraldFieldValue(): void {
    $repo   = new PhabricatorRepository();
    $commit = (new PhabricatorRepositoryCommit())
      ->attachRepository($repo);

    $field = new DiffusionCommitHostedRepositoryHeraldField();

    $repo->setHosted(true);
    $this->assertTrue($field->getHeraldFieldValue($commit));

    $repo->setHosted(false);
    $this->assertFalse($field->getHeraldFieldValue($commit));
  }

}

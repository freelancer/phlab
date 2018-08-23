<?php

final class DiffusionCommitHostedRepositoryHeraldField
  extends DiffusionCommitHeraldField {

  const FIELDCONST = 'diffusion.commit.repository.hosted';

  public function getHeraldFieldName(): string {
    return pht('Repository is hosted');
  }

  public function getHeraldFieldValue($object): bool {
    return $object->getRepository()->isHosted();
  }

  protected function getHeraldFieldStandardType() {
    return self::STANDARD_BOOL;
  }

}

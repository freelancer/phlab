<?php

final class DiffusionCommitRevisionResigningReviewersHeraldField
  extends DiffusionCommitHeraldField {

  const FIELDCONST = 'diffusion.commit.revision.resigning';

  public function getHeraldFieldName(): string {
    return pht('Resigning reviewers');
  }

  public function getFieldGroupKey(): string {
    return HeraldRelatedFieldGroup::FIELDGROUPKEY;
  }

  public function getHeraldFieldValue($object): array {
    $revision = $this->getAdapter()->loadDifferentialRevision();

    if (!$revision) {
      return [];
    }

    $resignee_phids = [];
    foreach ($revision->getReviewers() as $reviewer) {
      if ($reviewer->isResigned()) {
        $resignee_phids[] = $reviewer->getReviewerPHID();
      }
    }

    return $resignee_phids;
  }

  protected function getHeraldFieldStandardType(): string {
    return self::STANDARD_PHID_LIST;
  }

  protected function getDatasource(): PhabricatorTypeaheadDatasource {
    return new DifferentialReviewerDatasource();
  }

}

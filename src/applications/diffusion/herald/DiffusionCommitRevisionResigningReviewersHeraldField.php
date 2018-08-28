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

    $resigning_reviewers = mfilter($revision->getReviewers(), 'isResigned');
    return mpull($resigning_reviewers, 'getReviewerPHID');
  }

  protected function getHeraldFieldStandardType(): string {
    return self::STANDARD_PHID_LIST;
  }

  protected function getDatasource(): PhabricatorTypeaheadDatasource {
    return new DifferentialReviewerDatasource();
  }

}

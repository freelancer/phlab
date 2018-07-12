<?php

final class DiffusionCommitRevisionResigningReviewersHeraldField
  extends DiffusionCommitHeraldField {

  const FIELDCONST = 'diffusion.commit.revision.resigning';

  public function getHeraldFieldName() {
    return pht('Resigning reviewers');
  }

  public function getFieldGroupKey() {
    return HeraldRelatedFieldGroup::FIELDGROUPKEY;
  }

  public function getHeraldFieldValue($object) {
    $revision = $this->getAdapter()->loadDifferentialRevision();

    if (!$revision) {
      return array();
    }

    $resignee_phids = array();
    foreach ($revision->getReviewers() as $reviewer) {
      if ($reviewer->isResigned()) {
        $resignee_phids[] = $reviewer->getReviewerPHID();
      }
    }

    return $resignee_phids;
  }

  protected function getHeraldFieldStandardType() {
    return self::STANDARD_PHID_LIST;
  }

  protected function getDatasource() {
    return new DifferentialReviewerDatasource();
  }

}

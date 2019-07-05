<?php

final class PhabricatorOwnerProjectsCustomField
  extends PhabricatorProjectCustomEdgeField {

  public function getFieldKey(): string {
    return 'phlab:owner-projects';
  }

  public function getFieldName(): string {
    return pht('Owner Projects');
  }

  public function getFieldDescription(): ?string {
    // TODO: Implement this method.
    return null;
  }

  public function isFieldEnabled(): bool {
    // TODO: We should possibly disable this field for non-team projects.
    return true;
  }

  protected function getDatasource(): PhabricatorTypeaheadDatasource {
    // TODO: We should filter this datasource so as to only include "feature" projects.
    return new PhabricatorProjectDatasource();
  }

  protected function getEdgeType(): PhabricatorEdgeType {
    return new PhabricatorOwnedByProjectEdgeType();
  }

  public function shouldAppearInEditView(): bool {
    return false;
  }

  public function shouldAppearInEditEngine(): bool {
    // TODO: Should this be `true`?
    return false;
  }

  public function getInstructionsForEdit(): ?string {
    // TODO: Implement this method.
    return null;
  }

  public function shouldAppearInPropertyView(): bool {
    return true;
  }

}

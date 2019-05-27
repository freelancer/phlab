<?php

final class PhabricatorOwnedProjectsCustomField
  extends PhabricatorProjectCustomEdgeField {

  public function getFieldKey(): string {
    return 'phlab:owned-projects';
  }

  public function getFieldName(): string {
    return pht('Owned Projects');
  }

  public function getFieldDescription(): ?string {
    // TODO: Implement this method.
    return null;
  }

  public function isFieldEnabled(): bool {
    // TODO: We should possibly disable this field for non-team projects.
    return true;
  }

  /**
   * @todo We shouldn't need to implement this method since this field is not
   *   editable.
   */
  protected function getDatasource(): PhabricatorTypeaheadDatasource {
    return new PhabricatorProjectDatasource();
  }

  protected function getEdgeType(): PhabricatorEdgeType {
    return new PhabricatorOwnsProjectEdgeType();
  }

  public function shouldAppearInEditView(): bool {
    return true;
  }

  public function shouldAppearInPropertyView(): bool {
    return true;
  }

}

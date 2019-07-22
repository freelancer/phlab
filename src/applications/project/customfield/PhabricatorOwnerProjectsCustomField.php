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

  protected function getEdgeType(): PhabricatorEdgeType {
    return new PhabricatorOwnedByProjectEdgeType();
  }

  public function shouldAppearInPropertyView(): bool {
    return true;
  }

}

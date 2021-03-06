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

  protected function getDatasource(): PhabricatorTypeaheadDatasource {
    $subtypes = PhabricatorEnv::getEnvConfig('phlab.projects.ownable-subtypes');

    return (new PhlabProjectDatasource())
      ->setParameters(['subtypes' => $subtypes]);
  }

  protected function getEdgeType(): PhabricatorEdgeType {
    return new PhabricatorOwnsProjectEdgeType();
  }

  public function shouldAppearInEditView(): bool {
    return true;
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

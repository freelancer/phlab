<?php

final class PhabricatorRelatedProjectsCustomField
  extends PhabricatorProjectStoredCustomField {

  public function getFieldKey(): string {
    return 'phlab:related-projects';
  }

  public function getFieldName(): string {
    return pht('Related Projects');
  }

  public function getFieldDescription(): ?string {
    // TODO: Implement this method.
    return null;
  }

  public function isFieldEnabled(): bool {
    // TODO: We should possibly disable this field for non-team projects.
    return true;
  }

  public function getValueForStorage(): ?string {
    $value = $this->getValue();

    // There's no need to explicitly store an empty array.
    if (!$value) {
      return null;
    }

    return phutil_json_encode(array_values($value));
  }

  public function setValueFromStorage($value): void {
    $this->setValue(json_decode($value, true));
  }

  public function shouldAppearInApplicationTransactions(): bool {
    return true;
  }

  public function shouldAppearInEditView(): bool {
    return true;
  }

  public function shouldAppearInEditEngine(): bool {
    // TODO: Should this be `true`?
    return false;
  }

  public function readValueFromRequest(AphrontRequest $request): void {
    $this->setValue($request->getArr($this->getFieldKey()));
  }

  public function getRequiredHandlePHIDsForEdit(): array {
    return coalesce($this->getValue(), []);
  }

  public function getInstructionsForEdit(): ?string {
    // TODO: Implement this method.
    return null;
  }

  public function renderEditControl(array $handles): AphrontFormControl {
    $datasource = new PhabricatorProjectDatasource();
    $value      = coalesce($this->getValue(), []);

    return (new AphrontFormTokenizerControl())
      ->setViewer($this->getViewer())
      ->setLabel($this->getFieldName())
      ->setName($this->getFieldKey())
      ->setValue($value)
      ->setDatasource($datasource);
  }

  public function shouldAppearInPropertyView(): bool {
    return true;
  }

  public function renderPropertyViewValue(array $handles): ?PhutilSafeHTML {
    return $this->renderHandleList($handles);
  }

  public function getRequiredHandlePHIDsForPropertyView(): array {
    return coalesce($this->getValue(), []);
  }

}

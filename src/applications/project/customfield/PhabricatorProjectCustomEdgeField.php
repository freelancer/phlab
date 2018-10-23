<?php

abstract class PhabricatorProjectCustomEdgeField
  extends PhabricatorProjectCustomField {

  private $value = [];

  final public function getValue(): array {
    return $this->value;
  }

  final public function setValue(array $value): void {
    $this->value = $value;
  }

  abstract protected function getDatasource(): PhabricatorTypeaheadDatasource;
  abstract protected function getEdgeType(): PhabricatorEdgeType;

  final protected function getEdgeConstant(): int {
    return $this->getEdgeType()->getEdgeConstant();
  }

  final public function readValueFromObject(PhabricatorCustomFieldInterface $object): void {
    $edges = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getObject()->getPHID(),
      $this->getEdgeConstant());

    $this->setValue($edges);
  }

  final public function getValueForStorage(): array {
    return ['=' => array_fuse($this->getValue())];
  }

  final public function setValueFromStorage($value): void {
    $this->setValue(array_values($value['=']));
  }

  final public function shouldAppearInApplicationTransactions(): bool {
    return true;
  }

  final public function getApplicationTransactionType(): string {
    return PhabricatorTransactions::TYPE_EDGE;
  }

  final public function getApplicationTransactionMetadata(): array {
    return [
      'edge:type' => $this->getEdgeConstant(),
    ];
  }

  final public function readValueFromRequest(AphrontRequest $request): void {
    $this->setValue($request->getArr($this->getFieldKey()));
  }

  final public function getRequiredHandlePHIDsForEdit(): array {
    return $this->getValue();
  }

  final public function renderEditControl(array $handles): AphrontFormControl {
    return (new AphrontFormTokenizerControl())
      ->setViewer($this->getViewer())
      ->setLabel($this->getFieldName())
      ->setName($this->getFieldKey())
      ->setValue($this->getValue())
      ->setDatasource($this->getDatasource());
  }

  final public function renderPropertyViewValue(array $handles): ?PhutilSafeHTML {
    return $this->renderHandleList($handles);
  }

  final public function getRequiredHandlePHIDsForPropertyView(): array {
    return $this->getValue();
  }

}

<?php

abstract class PhabricatorProjectStoredCustomField
  extends PhabricatorProjectCustomField {

  private $value;

  final public function getValue() {
    return $this->value;
  }

  final public function setValue($value): void {
    $this->value = $value;
  }

  final public function shouldUseStorage(): bool {
    return true;
  }

  public function getValueForStorage() {
    return $this->getValue();
  }

  public function setValueFromStorage($value): void {
    $this->setValue($value);
  }

}

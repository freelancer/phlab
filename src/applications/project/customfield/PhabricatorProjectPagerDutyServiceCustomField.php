<?php

final class PhabricatorProjectPagerDutyServiceCustomField
  extends PhabricatorProjectCustomField {

  private $serviceName;

  public function getFieldKey(): string {
     return 'projects:pagerduty:service';
   }

  public function getFieldName(): string {
    return pht('Pagerduty Service');
  }

  public function getFieldDescription(): ?string {
    return pht(
      'Corresponding pagerduty service to be triggered '
      .'for any incidents associated with this project');
  }

  public function shouldUseStorage(): bool {
    return true;
  }

  public function shouldAppearInApplicationTransactions(): bool {
    return true;
  }

  public function shouldAppearInEditView(): bool {
    return true;
  }

  public function shouldAppearInEditEngine(): bool {
    return true;
  }

  public function shouldAppearInPropertyView(): bool {
    return true;
  }

  public function getValueForStorage(): ?string {
    return $this->serviceName;
  }

  /**
   * @return string|null Service name used for calling pagerduty API
   */
  public function getValueForApiCall(): ?string {
    return $this->serviceName;
  }

  public function setValueFromStorage($value) {
    $this->serviceName = $value;
    return $this;
  }

  public function readValueFromRequest(AphrontRequest $request): void {
    $this->serviceName = $request->getStr($this->getFieldKey());
  }

  public function renderPropertyViewValue(array $handles): ?string {
    return $this->serviceName;
  }

  public function renderEditControl(array $handles): AphrontFormControl {
    return (new AphrontFormTextControl())
      ->setLabel(pht('%s', $this->getFieldName()))
      ->setName($this->getFieldKey())
      ->setValue($this->serviceName);
  }

  /**
   * Validate transactions for an object. This allows you to raise an error
   * when a transaction would set a field to an invalid value, or when a field
   * is required but no transactions provide value.
   *
   * @param PhabricatorLiskDAO Editor applying the transactions.
   * @param string Transaction type. This type is always
   *   `PhabricatorTransactions::TYPE_CUSTOMFIELD`, it is provided for
   *   convenience when constructing exceptions.
   * @param list<PhabricatorApplicationTransaction> Transactions being applied,
   *   which may be empty if this field is not being edited.
   * @return list<PhabricatorApplicationTransactionValidationError> Validation
   *   errors.
   *
   * @task appxaction
   */
  public function validateApplicationTransactions(
    PhabricatorApplicationTransactionEditor $editor,
    $type,
    array $xactions): array {

    $errors = array();

    $services = PhabricatorEnv::getEnvConfig('pagerduty.integration_keys');

    foreach ($xactions as $xaction) {
      if (!in_array($xaction->getNewValue(), array_keys($services))) {
        $error = new PhabricatorApplicationTransactionValidationError(
          $type,
          pht('Non-existent service'),
          pht('%s is not a valid Pagerduty service', $xaction->getNewValue()),
          $xaction
        );
        $errors[] = $error;
      }
    }

    return $errors;
  }
}

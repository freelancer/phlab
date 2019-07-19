<?php

final class PhabricatorProjectSprintCycleCustomField
  extends PhabricatorProjectCustomField {

  private $start;
  private $end;

  public function getFieldKey(): string {
     return 'projects:sprint:cycle';
   }

  public function getFieldName(): string {
    return pht('Sprint Cycle');
  }

  public function getFieldDescription(): ?string {
    // TODO: Implement this method.
    return null;
  }

  public function shouldUseStorage(): bool {
    return true;
  }

  public function getValueForStorage(): ?string {
    if ($this->start === null && $this->end === null) {
      return null;
    }

    return phutil_json_encode([$this->start, $this->end]);
  }

  public function setValueFromStorage($value) {
    if ($value !== null) {
      list($this->start, $this->end) = phutil_json_decode($value);
    } else {
      $this->start = null;
      $this->end   = null;
    }

    return $this;
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
    $control = $this->newDateControl();
    $control->setViewer($request->getViewer());

    list($this->start, $this->end) = $control->readValueFromRequest($request);
  }

  public function getInstructionsForEdit(): ?string {
    // TODO: Implement this method.
    return null;
  }

  public function renderEditControl(array $handles): AphrontFormControl {
    return $this->newDateControl();
  }

  public function shouldAppearInPropertyView(): bool {
    return true;
  }

  public function renderPropertyViewValue(array $handles): ?string {
    if ($this->start === null && $this->end === null) {
      return null;
    }

    return pht(
      '%s to %s',
      phabricator_date($this->start, $this->getViewer()),
      phabricator_date($this->end, $this->getViewer()));
  }

  private function newDateControl(): AphrontFormDateRangeControl {
    return (new AphrontFormDateRangeControl())
      ->setViewer($this->getViewer())
      ->setLabel($this->getFieldName())
      ->setName($this->getFieldKey())
      ->setValue([$this->start, $this->end])
      ->setIsTimeDisabled(true);
  }

}

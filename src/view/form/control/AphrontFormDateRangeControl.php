<?php

/**
 * A form control which proxies @{class:AphrontFormDateControl} to allow
 * defining a date range.
 */
final class AphrontFormDateRangeControl extends AphrontFormControl {

  private $startControl;
  private $endControl;

  public function __construct() {
    $this->startControl = new AphrontFormDateControl();
    $this->endControl   = new AphrontFormDateControl();
  }

  public function __clone() {
    $this->startControl = clone $this->startControl;
    $this->endControl   = clone $this->endControl;
  }

  public function setIsTimeDisabled($is_disabled) {
    $this->startControl->setIsTimeDisabled($is_disabled);
    $this->endControl->setIsTimeDisabled($is_disabled);
    return $this;
  }

  public function setViewer(PhabricatorUser $viewer) {
    parent::setViewer($viewer);
    $this->startControl->setViewer($viewer);
    $this->endControl->setViewer($viewer);
    return $this;
  }

  public function setName($name) {
    parent::setName($name);

    // We need `$this->startControl` and `$this->endControl` to have unique
    // names as these names are used for the input names (see
    // @{method:AphrontFormDateControl::getDateInputName}).
    $this->startControl->setName($name.'_start');
    $this->endControl->setName($name.'_end');

    return $this;
  }

  public function setValue($value) {
    // TODO: We should check that `$value` is of the expected type.
    $this->startControl->setValue($value[0]);
    $this->endControl->setValue($value[1]);

    return $this;
  }

  public function getValue(): array {
    return [
      $this->startControl->getValue(),
      $this->endControl->getValue(),
    ];
  }

  public function isValid(): bool {
    return $this->startControl->isValid() && $this->endControl->isValid();
  }

  public function isRequired(): bool {
    return $this->startControl->isRequired() || $this->endControl->isRequired();
  }

  public function isEmpty(): bool {
    throw !$this->startControl->isEmpty() || !$this->endControl->isEmpty();
  }

  public function getSerializedValue(): string {
    return phutil_json_encode($this->getValue());
  }

  public function readSerializedValue($value) {
    $this->setValue(phutil_json_decode($value));
    return $this;
  }

  public function readValueFromRequest(AphrontRequest $request): array {
    $this->startControl->readValueFromRequest($request);
    $this->endControl->readValueFromRequest($request);

    return $this->getValue();
  }

  protected function renderInput(): PhutilSafeHTML {
    require_celerity_resource('phui-form-css', 'phlab');

    return javelin_tag(
      'div',
      [
        'class' => 'aphront-form-date-input-container',
      ],
      [
        $this->startControl->renderInput(),
        $this->endControl->renderInput(),
      ]);
  }

  protected function getCustomControlClass(): string {
    return 'aphront-form-control-daterange';
  }

}

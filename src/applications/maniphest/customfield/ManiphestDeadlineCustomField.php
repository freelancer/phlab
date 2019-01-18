<?php

final class ManiphestDeadlineCustomField extends ManiphestCustomField {

  private $value;

  public function getFieldKey(): string {
    return 'maniphest:deadline';
  }

  public function getFieldName(): string {
    return pht('Deadline');
  }

  public function shouldUseStorage(): bool {
    return true;
  }

  public function getValueForStorage(): ?int {
    return $this->value;
  }

  public function setValueFromStorage($value): void {
    $this->value = $value;
  }

  public function shouldAppearInApplicationTransactions(): bool {
    return true;
  }

  public function getApplicationTransactionTitle(PhabricatorApplicationTransaction $xaction): PhutilSafeHTML {
    $author_phid = $xaction->getAuthorPHID();
    $viewer      = $this->getViewer();

    $old_value = $xaction->getOldValue();
    $new_value = $xaction->getNewValue();

    if ($old_value === null) {
      return pht(
        '%s set task deadline to %s.',
        $xaction->renderHandleLink($author_phid),
        phabricator_date($new_value, $viewer));
    } else if ($new_value === null) {
      return pht(
        '%s removed task deadline.',
        $xaction->renderHandleLink($author_phid));
    } else {
      return pht(
        '%s changed task deadline from %s to %s.',
        $xaction->renderHandleLink($author_phid),
        phabricator_date($old_value, $viewer),
        phabricator_date($new_value, $viewer));
    }
  }

  public function getApplicationTransactionTitleForFeed(PhabricatorApplicationTransaction $xaction): PhutilSafeHTML {
    $author_phid = $xaction->getAuthorPHID();
    $object_phid = $xaction->getObjectPHID();
    $viewer      = $this->getViewer();

    $old_value = $xaction->getOldValue();
    $new_value = $xaction->getNewValue();

    if ($old_value === null) {
      return pht(
        '%s set deadline for %s to %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($object_phid),
        phabricator_date($new_value, $viewer));
    } else if ($new_value === null) {
      return pht(
        '%s removed deadline for %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($object_phid));
    } else {
      return pht(
        '%s changed deadline for %s from %s to %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($object_phid),
        phabricator_date($old_value, $viewer),
        phabricator_date($new_value, $viewer));
    }
  }

  public function shouldAppearInEditView(): bool {
    return true;
  }

  public function readValueFromRequest(AphrontRequest $request): void {
    $control = $this->newDateControl();
    $control->setUser($request->getUser());

    $this->value = $control->readValueFromRequest($request);
  }

  public function renderEditControl(array $handles): AphrontFormControl {
    return $this->newDateControl();
  }

  public function shouldAppearInPropertyView(): bool {
    return true;
  }

  public function renderPropertyViewValue(array $handles): ?string {
    if ($this->value === null) {
      return null;
    }

    return phabricator_date($this->value, $this->getViewer());
  }

  /**
   * NOTE: This method was largely copied from
   * @{method:PhabricatorStandardCustomFieldDate::newDateControl}.
   */
  private function newDateControl(): AphrontFormDateControl {
    return (new AphrontFormDateControl())
      ->setViewer($this->getViewer())
      ->setLabel($this->getFieldName())
      ->setName($this->getFieldKey())
      ->setValue($this->value)
      ->setAllowNull(true)
      ->setIsTimeDisabled(true);
  }

}

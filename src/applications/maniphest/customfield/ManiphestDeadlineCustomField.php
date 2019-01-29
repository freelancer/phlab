<?php

final class ManiphestDeadlineCustomField extends ManiphestCustomField {

  private $epoch;
  private $triggerPHID;

  public function getFieldKey(): string {
     return 'maniphest:deadline';
   }

  public function getFieldName(): string {
    return pht('Deadline');
  }

  public function shouldUseStorage(): bool {
    return true;
  }

  public function getValueForStorage(): ?string {
    if ($this->epoch === null) {
      return null;
    }

    return phutil_json_encode([
      'epoch'       => $this->epoch,
      'triggerPHID' => $this->triggerPHID,
    ]);
  }

  public function setValueFromStorage($value): void {
    if ($value !== null) {
      $value = phutil_json_decode($value);
    } else {
      $value = [];
    }

    $this->epoch       = idx($value, 'epoch');
    $this->triggerPHID = idx($value, 'triggerPHID');
  }

  public function shouldAppearInApplicationSearch(): bool {
    return true;
  }

  public function buildFieldIndexes(): array {
    $indexes = [];

    if ($this->epoch !== null) {
      $indexes[] = $this->newNumericIndex($this->epoch);
    }

    return $indexes;
  }

  public function buildOrderIndex(): ?ManiphestCustomFieldNumericIndex {
    return $this->newNumericIndex(0);
  }

  public function readApplicationSearchValueFromRequest(PhabricatorApplicationSearchEngine $engine, AphrontRequest $request): array {
    $key = $this->getFieldKey();

    return [
      'min' => $request->getStr($key.'.min'),
      'max' => $request->getStr($key.'.max'),
    ];
  }

  public function applyApplicationSearchConstraintToQuery(PhabricatorApplicationSearchEngine $engine, PhabricatorCursorPagedPolicyAwareQuery $query, $value): void {
    $viewer = $this->getViewer();

    if (!is_array($value)) {
      $value = [];
    }

    $min_str = idx($value, 'min', '');
    if (strlen($min_str)) {
      $min = PhabricatorTime::parseLocalTime($min_str, $viewer);
    } else {
      $min = null;
    }

    $max_str = idx($value, 'max', '');
    if (strlen($max_str)) {
      $max = PhabricatorTime::parseLocalTime($max_str, $viewer);
    } else {
      $max = null;
    }

    if ($min !== null || $max !== null) {
      $query->withApplicationSearchRangeConstraint(
        $this->newNumericIndex(null),
        $min,
        $max);
    }
  }

  public function appendToApplicationSearchForm(PhabricatorApplicationSearchEngine $engine, AphrontFormView $form, $value): void {
    $key = $this->getFieldKey();

    if (!is_array($value)) {
      $value = [];
    }

    $form
      ->appendChild(
        (new AphrontFormTextControl())
          ->setLabel(pht('Deadline After'))
          ->setName($key.'.min')
          ->setValue(idx($value, 'min')))
      ->appendChild(
        (new AphrontFormTextControl())
          ->setLabel(pht('Deadline Before'))
          ->setName($key.'.max')
          ->setValue(idx($value, 'max')));
  }

  public function shouldAppearInApplicationTransactions(): bool {
    return true;
  }

  public function applyApplicationTransactionInternalEffects(PhabricatorApplicationTransaction $xaction): void {
    if ($xaction->getOldValue() !== null) {
      $old_value = phutil_json_decode($xaction->getOldValue());
    } else {
      $old_value = null;
    }

    if ($xaction->getNewValue() !== null) {
      $new_value = phutil_json_decode($xaction->getNewValue());
    } else {
      $new_value = null;
    }

    if ($new_value !== null) {
      $epoch        = $new_value['epoch'];
      $trigger_phid = $new_value['triggerPHID'];

      // TODO: If the existing trigger has already fired then we should
      // possibly create a new trigger instead as the existing trigger will not
      // fire again.
      if ($trigger_phid !== null) {
        $trigger = $this->loadTrigger($trigger_phid);
      } else {
        $trigger = new PhabricatorWorkerTrigger();
      }

      // Schedule a reminder notification 24 hours before the deadline.
      //
      // TODO: We should possibly allow the epoch to be customizable.
      $clock = new PhabricatorOneTimeTriggerClock([
        'epoch' => ($epoch - phutil_units('24 hours in seconds')),
      ]);

      $action = new PhabricatorScheduleTaskTriggerAction([
        'class'   => ManiphestDeadlineReminderWorker::class,
        'data'    => [],
        'options' => [
          'priority' => PhabricatorWorker::PRIORITY_DEFAULT,
        ],
      ]);

      $trigger
        ->setAction($action)
        ->setClock($clock)
        ->save();

      // Modify the transaction data to store the trigger PHID.
      $new_value['triggerPHID'] = $trigger->getPHID();
      $xaction->setNewValue(phutil_json_encode($new_value));
    } else {
      // TODO: We should possibly not delete the trigger if it has already
      // fired.
      $trigger = $this->loadTrigger($old_value['triggerPHID']);
      $trigger->delete();
    }
  }

  public function getApplicationTransactionTitle(PhabricatorApplicationTransaction $xaction) {
    $author_phid = $xaction->getAuthorPHID();
    $viewer      = $this->getViewer();

    if ($xaction->getOldValue() !== null) {
      $old_value = phutil_json_decode($xaction->getOldValue());
      $old_epoch = $old_value['epoch'];
    } else {
      $old_epoch = null;
    }

    if ($xaction->getNewValue() !== null) {
      $new_value = phutil_json_decode($xaction->getNewValue());
      $new_epoch = $new_value['epoch'];
    } else {
      $new_epoch = null;
    }

    if ($old_epoch === null) {
      return pht(
        '%s set task deadline to %s.',
        $xaction->renderHandleLink($author_phid),
        phabricator_date($new_epoch, $viewer));
    } else if ($new_epoch === null) {
      return pht(
        '%s removed task deadline.',
        $xaction->renderHandleLink($author_phid));
    } else {
      return pht(
        '%s changed task deadline from %s to %s.',
        $xaction->renderHandleLink($author_phid),
        phabricator_date($old_epoch, $viewer),
        phabricator_date($new_epoch, $viewer));
    }
  }

  public function getApplicationTransactionTitleForFeed(PhabricatorApplicationTransaction $xaction) {
    $author_phid = $xaction->getAuthorPHID();
    $object_phid = $xaction->getObjectPHID();
    $viewer      = $this->getViewer();

    if ($xaction->getOldValue() !== null) {
      $old_value = phutil_json_decode($xaction->getOldValue());
      $old_epoch = $old_value['epoch'];
    } else {
      $old_epoch = null;
    }

    if ($xaction->getNewValue() !== null) {
      $new_value = phutil_json_decode($xaction->getNewValue());
      $new_epoch = $new_value['epoch'];
    } else {
      $new_epoch = null;
    }

    if ($old_epoch === null) {
      return pht(
        '%s set deadline for %s to %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($object_phid),
        phabricator_date($new_epoch, $viewer));
    } else if ($new_epoch === null) {
      return pht(
        '%s removed deadline for %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($object_phid));
    } else {
      return pht(
        '%s changed deadline for %s from %s to %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($object_phid),
        phabricator_date($old_epoch, $viewer),
        phabricator_date($new_epoch, $viewer));
    }
  }

  public function shouldAppearInEditView(): bool {
    return true;
  }

  public function readValueFromRequest(AphrontRequest $request): void {
    $control = $this->newDateControl();
    $control->setUser($request->getUser());

    $value = $control->readValueFromRequest($request);

    if ($value !== null) {
      $this->epoch = (int)$value;
    } else {
      $this->epoch = null;
    }
  }

  public function renderEditControl(array $handles): AphrontFormControl {
    return $this->newDateControl();
  }

  public function shouldAppearInPropertyView(): bool {
    return true;
  }

  public function renderPropertyViewValue(array $handles): ?string {
    if ($this->epoch === null) {
      return null;
    }

    return phabricator_date($this->epoch, $this->getViewer());
  }

  private function loadTrigger(string $phid): PhabricatorWorkerTrigger {
    return (new PhabricatorWorkerTriggerQuery())
      ->setViewer($this->getViewer())
      ->withPHIDs([$phid])
      ->executeOne();
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
      ->setValue($this->epoch)
      ->setAllowNull(true)
      ->setIsTimeDisabled(true);
  }

}

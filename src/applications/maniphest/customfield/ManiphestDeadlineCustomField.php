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
    if ($xaction->getNewValue() === null) {
      return;
    }

    $new_value = phutil_json_decode($xaction->getNewValue());

    if ($new_value['triggerPHID'] === null) {
      $new_value['triggerPHID'] = PhabricatorPHID::generateNewPHID(
        PhabricatorWorkerTriggerPHIDType::TYPECONST);
      $xaction->setNewValue(phutil_json_encode($new_value));
    }
  }

  public function applyApplicationTransactionExternalEffects(PhabricatorApplicationTransaction $xaction): void {
    parent::applyApplicationTransactionExternalEffects($xaction);

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

      $trigger = $this->loadTrigger($trigger_phid);

      if ($trigger === null) {
        $trigger = new PhabricatorWorkerTrigger();
        $trigger->setPHID($trigger_phid);
      }

      // Schedule a reminder notification 24 hours before the deadline.
      //
      // TODO: We should possibly allow the epoch to be customizable.
      // TODO: We probably shouldn't schedule triggers if the trigger
      //       epoch is in the past.
      $clock = new PhabricatorOneTimeTriggerClock([
        'epoch' => ($epoch - phutil_units('24 hours in seconds')),
      ]);

      $action = new PhabricatorScheduleTaskTriggerAction([
        'class'   => ManiphestDeadlineReminderWorker::class,
        'data'    => [
          'objectPHID' => $xaction->getObjectPHID(),
        ],
        'options' => [
          'objectPHID' => $xaction->getObjectPHID(),
          'priority'   => PhabricatorWorker::PRIORITY_DEFAULT,
        ],
      ]);

      $trigger
        ->setAction($action)
        ->setClock($clock);

      // `$trigger->getEvent()` will throw even if the trigger has no event.
      try {
        $event = $trigger->getEvent();
      } catch (PhabricatorDataNotAttachedException $ex) {
        $event = null;
      }

      $trigger->openTransaction();
        $trigger->save();

        // If the trigger has already fired, delete the trigger event and
        // create a new one. This logic is copied from
        // @{method:PhabricatorTriggerDaemon::scheduleTriggers}.
        if ($event !== null) {
          $last_epoch = null;
          $next_epoch = $trigger->getNextEventEpoch($last_epoch, false);

          $new_event = PhabricatorWorkerTriggerEvent::initializeNewEvent($trigger)
            ->setLastEventEpoch($last_epoch)
            ->setNextEventEpoch($next_epoch);

          $event->delete();
          $new_event->save();
        }
      $trigger->saveTransaction();
    } else {
      // TODO: We should possibly not delete the trigger if it has already
      // fired.
      $trigger = $this->loadTrigger($old_value['triggerPHID']);

      if ($trigger !== null) {
        $trigger->delete();
      }
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

  private function loadTrigger(string $phid): ?PhabricatorWorkerTrigger {
    return (new PhabricatorWorkerTriggerQuery())
      ->setViewer($this->getViewer())
      ->withPHIDs([$phid])
      ->needEvents(true)
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

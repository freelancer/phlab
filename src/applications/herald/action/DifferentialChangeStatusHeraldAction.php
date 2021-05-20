<?php

final class DifferentialChangeStatusHeraldAction
  extends HeraldAction {

  const ACTIONCONST = 'differential.revision.status';

  const DO_CHANGE = 'do.update';
  const DO_IGNORE = 'do.ignore';

  public function getHeraldActionName() {
    return pht('Change diff status');
  }

  public function supportsObject($object) {
    return ($object instanceof DifferentialRevision);
  }

  public function supportsRuleType($rule_type) {
    return $rule_type == HeraldRuleTypeConfig::RULE_TYPE_GLOBAL;
  }

  public function applyEffect($object, HeraldEffect $effect) {
    if (
        $object->isClosed() ||
        $object->isAbandoned() ||
        $object->isDraft()
    ) {
        // silently ignore the objects with these statuses
        return true;
    }

    $status = $effect->getTarget();

    if ($status === DifferentialRevisionStatus::CHANGES_PLANNED) {
        if (!$this->isValidPlanChangesEffect()) {
            // silently fail
            return true;
        }
    }

    return $object->setModernRevisionStatus($status)->save();
  }


  public function isValidPlanChangesEffect() {
    $adapter = $this->getAdapter();
    $xactions = $adapter->getAppliedTransactions();

    if (!$xactions) {
        return true;
    }
    foreach ($xactions as $xaction) {
        // We need to check if there's a transaction like `request-review`
        // that caused the diff to be in `needs-review` status.
        if ($xaction->getTransactionType() == DifferentialRevisionRequestReviewTransaction::TRANSACTIONTYPE) {
            return false;
        }
        // However, if there's a more recent update, allow `plan-changes` to take effect
        if ($xaction->getTransactionType() == DifferentialRevisionUpdateTransaction::TRANSACTIONTYPE) {
            return true;
        }
    }

    return false;
  }

  protected function getDatasource() {
    return new DifferentialRevisionStatusDatasource();
  }

  protected function getDatasourceValueMap() {
    $map = DifferentialRevisionStatus::getAll();
    return mpull($map, 'getDisplayName', 'getKey');
  }

  public function renderActionDescription($value) {
    return pht('Change diff status to %s.', $value);
  }

  public function getPHIDsAffectedByAction(HeraldActionRecord $record) {
    return $record->getTarget();
  }

  public function getHeraldActionValueType() {
    return id(new HeraldSelectFieldValue())
      ->setKey('differential.revision.status')
      ->setOptions($this->getDatasourceValueMap())
      ->setDefault(DifferentialRevisionStatus::CHANGES_PLANNED);
  }

  protected function getActionEffectMap() {
    return array(
      self::DO_IGNORE => array(
        'icon' => 'fa-times',
        'color' => 'grey',
        'name' => pht('Ignored'),
      ),
      self::DO_CHANGE => array(
        'icon' => 'fa-flag',
        'name' => pht('Applied'),
      ),
    );
  }
}

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
        return true;
    }

    $status = $effect->getTarget();
    return $object->setModernRevisionStatus($status)->save();
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

//   protected function renderActionEffectDescription($type, $data) {
//     switch ($type) {
//       case self::DO_IGNORE:
//         return pht(
//           'Already marked with %s flag.',
//           var_export($type, true));
//       case self::DO_FLAG:
//         return pht(
//           'Marked with "%s" flag.',
//           var_export($type, true));
//     }
//   }
}

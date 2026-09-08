<?php

final class PhlabProjectTriggerManiphestSubstatusRule
  extends PhabricatorProjectTriggerRule {

  const TRIGGERTYPE = 'task.substatus';

  const FIELD_KEY = 'std:maniphest:general.sub-status.type';
  const RAW_FIELD_KEY = 'general.sub-status.type';

  public function getSelectControlName() {
    return pht('Change Substatus to');
  }

  protected function isSelectableRule() {
    return (bool)$this->getOptions();
  }

  protected function assertValidRuleRecordFormat($value) {
    if (!is_string($value)) {
      throw new Exception(
        pht(
          'Substatus rule value should be a string, but is not '.
          '(value is "%s").',
          phutil_describe_type($value)));
    }
  }

  protected function assertValidRuleRecordValue($value) {
    $options = $this->getOptions();
    if (!isset($options[$value])) {
      throw new Exception(
        pht(
          'Task Substatus value ("%s") is not valid. Valid values are: %s.',
          $value,
          implode(', ', array_keys($options))));
    }
  }

  protected function newDropTransactions($object, $value) {
    $field = PhabricatorCustomField::getObjectField(
      $object,
      PhabricatorCustomField::ROLE_APPLICATIONTRANSACTIONS,
      self::FIELD_KEY);
    if (!$field) {
      throw new Exception(
        pht(
          'Unable to apply the Substatus trigger because custom field "%s" '.
          'is not configured.',
          self::RAW_FIELD_KEY));
    }

    id(new PhabricatorCustomFieldList(array($field)))
      ->setViewer($this->getViewer())
      ->readFieldsFromStorage($object);

    return array(
      $this->newTransaction()
        ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
        ->setMetadataValue('customfield:key', self::FIELD_KEY)
        ->setOldValue($field->getOldValueForApplicationTransactions())
        ->setNewValue($value),
    );
  }

  protected function newDropEffects($value) {
    $name = idx($this->getOptions(), $value, $value);

    $content = pht(
      'Change Substatus to %s.',
      phutil_tag('strong', array(), $name));

    return array(
      $this->newEffect()
        ->setIcon('fa-tag')
        ->setColor('blue')
        ->setContent($content),
    );
  }

  protected function getDefaultValue() {
    return head_key($this->getOptions());
  }

  protected function getPHUIXControlType() {
    return 'select';
  }

  protected function getPHUIXControlSpecification() {
    $options = $this->getOptions();

    return array(
      'options' => $options,
      'order' => array_keys($options),
    );
  }

  public function getRuleViewLabel() {
    return pht('Change Substatus');
  }

  public function getRuleViewDescription($value) {
    $name = idx($this->getOptions(), $value, $value);

    return pht(
      'Change task Substatus to %s.',
      phutil_tag('strong', array(), $name));
  }

  public function getRuleViewIcon($value) {
    return id(new PHUIIconView())
      ->setIcon('fa-tag', 'blue');
  }

  private function getOptions() {
    $definitions = PhabricatorEnv::getEnvConfig(
      'maniphest.custom-field-definitions');
    $definition = idx($definitions, self::RAW_FIELD_KEY, array());

    return idx($definition, 'options', array());
  }

}

<?php

final class PhlabProjectTriggerManiphestSubstatusRuleTestCase
  extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return array(
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    );
  }

  public function testRuleTemplate() {
    $env = PhabricatorEnv::beginScopedEnv();
    $env->overrideEnvConfig(
      'maniphest.custom-field-definitions',
      array(
        'general.sub-status.type' => array(
          'name' => 'Substatus',
          'type' => 'select',
          'options' => array(
            'none' => 'None',
            'ready' => 'Ready for Development',
          ),
        ),
      ));

    $template = id(new PhlabProjectTriggerManiphestSubstatusRule())
      ->newTemplate();

    $this->assertEqual('task.substatus', $template['type']);
    $this->assertEqual('Change Substatus to', $template['name']);
    $this->assertTrue($template['selectable']);
    $this->assertEqual('none', $template['defaultValue']);
    $this->assertEqual('select', $template['control']['type']);
    $this->assertEqual(
      array(
        'none' => 'None',
        'ready' => 'Ready for Development',
      ),
      $template['control']['specification']['options']);

    unset($env);
  }

  public function testRuleValueValidation() {
    $env = PhabricatorEnv::beginScopedEnv();
    $env->overrideEnvConfig(
      'maniphest.custom-field-definitions',
      array(
        'general.sub-status.type' => array(
          'name' => 'Substatus',
          'type' => 'select',
          'options' => array(
            'none' => 'None',
            'ready' => 'Ready for Development',
          ),
        ),
      ));

    $valid_record = id(new PhabricatorProjectTriggerRuleRecord())
      ->setType('task.substatus')
      ->setValue('ready');
    $valid_rule = id(new PhlabProjectTriggerManiphestSubstatusRule())
      ->setRecord($valid_record);

    $this->assertEqual(
      null,
      $valid_rule->getRuleRecordValueValidationException());

    $invalid_record = id(new PhabricatorProjectTriggerRuleRecord())
      ->setType('task.substatus')
      ->setValue('unknown');
    $invalid_rule = id(new PhlabProjectTriggerManiphestSubstatusRule())
      ->setRecord($invalid_record);

    $this->assertTrue(
      $invalid_rule->getRuleRecordValueValidationException() instanceof
      Exception);

    unset($env);
  }

  public function testDropTransaction() {
    $env = PhabricatorEnv::beginScopedEnv();
    $env->overrideEnvConfig(
      'maniphest.custom-field-definitions',
      array(
        'general.sub-status.type' => array(
          'name' => 'Substatus',
          'type' => 'select',
          'options' => array(
            'none' => 'None',
            'ready' => 'Ready for Development',
          ),
        ),
      ));

    $task = id(new ManiphestTask())
      ->setPHID(PhabricatorPHID::generateNewPHID(
        ManiphestTaskPHIDType::TYPECONST));

    $record = id(new PhabricatorProjectTriggerRuleRecord())
      ->setType('task.substatus')
      ->setValue('ready');

    $rule = id(new PhlabProjectTriggerManiphestSubstatusRule())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->setObject($task)
      ->setRecord($record);

    $xactions = $rule->getDropTransactions($task, 'ready');
    $xaction = head($xactions);

    $this->assertEqual(1, count($xactions));
    $this->assertEqual(
      PhabricatorTransactions::TYPE_CUSTOMFIELD,
      $xaction->getTransactionType());
    $this->assertEqual(
      'std:maniphest:general.sub-status.type',
      $xaction->getMetadataValue('customfield:key'));
    $this->assertEqual(null, $xaction->getOldValue());
    $this->assertEqual('ready', $xaction->getNewValue());

    unset($env);
  }

}

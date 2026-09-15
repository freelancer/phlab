<?php

/**
 * Test cases for PhlabManiphestSubstatusCustomField.
 */
final class PhlabManiphestSubstatusCustomFieldTestCase
  extends PhabricatorTestCase {

  public function testFieldIsBuiltFromPhlabConfiguration(): void {
    $env = $this->newScopedEnv([], $this->newDefinition());

    $fields = (new PhlabManiphestSubstatusCustomField())
      ->createFields(new ManiphestTask());

    $this->assertEqual(1, count($fields));

    $field = head($fields);
    $this->assertEqual(
      PhlabManiphestSubstatusCustomField::FIELD_KEY,
      $field->getFieldKey());
    $this->assertEqual('Substatus', $field->getFieldName());
    $this->assertEqual(
      'custom.general.sub-status.type',
      $field->getModernFieldKey());

    unset($env);
  }

  public function testFieldStandsDownWhileConfiguredInManiphest(): void {
    // Both sources defining the field would collide on the field key, so the
    // configured definition wins until it is removed.
    $env = $this->newScopedEnv(
      [PhlabManiphestSubstatusCustomField::RAW_FIELD_KEY => $this->newDefinition()],
      $this->newDefinition());

    $fields = (new PhlabManiphestSubstatusCustomField())
      ->createFields(new ManiphestTask());

    $this->assertEqual([], $fields);

    unset($env);
  }

  public function testNoFieldWithoutConfiguration(): void {
    $env = $this->newScopedEnv([], []);

    $fields = (new PhlabManiphestSubstatusCustomField())
      ->createFields(new ManiphestTask());

    $this->assertEqual([], $fields);

    unset($env);
  }

  public function testOptionsResolveFromEitherSource(): void {
    $expect = [
      'none' => 'None',
      'ready' => 'Ready for Development',
    ];

    $env = $this->newScopedEnv([], $this->newDefinition());
    $this->assertEqual(
      $expect,
      PhlabManiphestSubstatusCustomField::getOptions());
    unset($env);

    $env = $this->newScopedEnv(
      [PhlabManiphestSubstatusCustomField::RAW_FIELD_KEY => $this->newDefinition()],
      []);
    $this->assertEqual(
      $expect,
      PhlabManiphestSubstatusCustomField::getOptions());
    unset($env);
  }

  public function testEditFieldOffersACommentAction(): void {
    $env = $this->newScopedEnv([], $this->newDefinition());

    $edit_field = $this->newEditField();

    $this->assertEqual('Change Substatus', $edit_field->getCommentActionLabel());

    $action = $edit_field->getCommentAction();
    $this->assertTrue($action instanceof PhabricatorEditEngineSelectCommentAction);
    $this->assertEqual(
      [
        'none' => 'None',
        'ready' => 'Ready for Development',
      ],
      $action->getOptions());

    unset($env);
  }

  public function testEditFieldStillEditsTheCustomField(): void {
    $env = $this->newScopedEnv([], $this->newDefinition());

    $edit_field = $this->newEditField();

    // The transaction itself is built by the custom field, so the comment
    // action writes to the same storage as the edit form and the workboard
    // trigger.
    $this->assertTrue($edit_field instanceof PhabricatorCustomFieldEditField);
    $this->assertEqual(
      PhabricatorTransactions::TYPE_CUSTOMFIELD,
      $edit_field->getTransactionType());
    $this->assertEqual(
      PhlabManiphestSubstatusCustomField::FIELD_KEY,
      $edit_field->getCustomField()->getFieldKey());

    unset($env);
  }

  private function newEditField(): PhabricatorEditField {
    $field = head(
      (new PhlabManiphestSubstatusCustomField())
        ->createFields(new ManiphestTask()));

    $edit_fields = $field->getEditEngineFields(new ManiphestEditEngine());

    return head($edit_fields);
  }

  private function newDefinition(): array {
    return [
      'name' => 'Substatus',
      'type' => 'select',
      'options' => [
        'none' => 'None',
        'ready' => 'Ready for Development',
      ],
    ];
  }

  private function newScopedEnv(array $configured, array $phlab) {
    $env = PhabricatorEnv::beginScopedEnv();
    $env->overrideEnvConfig('maniphest.custom-field-definitions', $configured);
    $env->overrideEnvConfig('phlab.maniphest.substatus', $phlab);

    return $env;
  }

}

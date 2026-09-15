<?php

final class PhlabManiphestStatusNotesCustomFieldTestCase
  extends PhabricatorTestCase {

  public function testFieldIsBuiltFromPhlabConfiguration(): void {
    $env = $this->newScopedEnv([], $this->newDefinition());

    $fields = (new PhlabManiphestStatusNotesCustomField())
      ->createFields(new ManiphestTask());

    $this->assertEqual(1, count($fields));

    $field = head($fields);
    $this->assertEqual(
      PhlabManiphestStatusNotesCustomField::FIELD_KEY,
      $field->getFieldKey());
    $this->assertEqual('Status Notes', $field->getFieldName());
    $this->assertEqual(
      'custom.general.status-notes.type',
      $field->getModernFieldKey());

    unset($env);
  }

  public function testFieldStandsDownWhileConfiguredInManiphest(): void {
    $env = $this->newScopedEnv(
      [
        PhlabManiphestStatusNotesCustomField::RAW_FIELD_KEY =>
          $this->newDefinition(),
      ],
      $this->newDefinition());

    $fields = (new PhlabManiphestStatusNotesCustomField())
      ->createFields(new ManiphestTask());

    $this->assertEqual([], $fields);

    unset($env);
  }

  public function testNoFieldWithoutConfiguration(): void {
    $env = $this->newScopedEnv([], []);

    $fields = (new PhlabManiphestStatusNotesCustomField())
      ->createFields(new ManiphestTask());

    $this->assertEqual([], $fields);

    unset($env);
  }

  public function testEditFieldOffersACommentAction(): void {
    $env = $this->newScopedEnv([], $this->newDefinition());

    $edit_field = $this->newEditField();

    $this->assertEqual(
      'Change Status Notes',
      $edit_field->getCommentActionLabel());

    $action = $edit_field->getCommentAction();
    $this->assertTrue($action instanceof PhlabEditEngineTextCommentAction);
    /** @var PhlabEditEngineTextCommentAction $action */
    $this->assertEqual('text', $action->getPHUIXControlType());

    unset($env);
  }

  public function testEditFieldStillEditsTheCustomField(): void {
    $env = $this->newScopedEnv([], $this->newDefinition());

    $edit_field = $this->newEditField();

    $this->assertTrue($edit_field instanceof PhabricatorCustomFieldEditField);
    $this->assertEqual(
      PhabricatorTransactions::TYPE_CUSTOMFIELD,
      $edit_field->getTransactionType());
    $this->assertEqual(
      PhlabManiphestStatusNotesCustomField::FIELD_KEY,
      $edit_field->getCustomField()->getFieldKey());

    unset($env);
  }

  private function newEditField(): PhabricatorCustomFieldEditField {
    $field = head(
      (new PhlabManiphestStatusNotesCustomField())
        ->createFields(new ManiphestTask()));

    $edit_fields = $field->getEditEngineFields(new ManiphestEditEngine());

    /** @var PhabricatorCustomFieldEditField $edit_field */
    $edit_field = head($edit_fields);

    return $edit_field;
  }

  private function newDefinition(): array {
    return [
      'name' => 'Status Notes',
      'placeholder' => 'Add more context on the status of the ticket',
    ];
  }

  private function newScopedEnv(array $configured, array $phlab) {
    $env = PhabricatorEnv::beginScopedEnv();
    $env->overrideEnvConfig('maniphest.custom-field-definitions', $configured);
    $env->overrideEnvConfig('phlab.maniphest.status-notes', $phlab);

    return $env;
  }

}

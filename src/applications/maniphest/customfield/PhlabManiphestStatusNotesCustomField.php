<?php

/**
 * The Maniphest "Status Notes" field.
 *
 * Phlab owns this field so that it can offer a "Change Status Notes" action
 * in the comment area. Core builds comment actions from
 * `PhabricatorCustomField::newCommentAction()`, and the text field which
 * backs a configured field is final and returns null there, so the action can
 * only be attached by a field class we control.
 *
 * The field key is `std:maniphest:general.status-notes.type`, so stored
 * values and the `custom.general.status-notes.type` Conduit key are unchanged.
 */
final class PhlabManiphestStatusNotesCustomField
  extends ManiphestCustomField
  implements PhabricatorStandardCustomFieldInterface {

  const RAW_FIELD_KEY = 'general.status-notes.type';
  const FIELD_KEY = 'std:maniphest:general.status-notes.type';

  public function getStandardCustomFieldNamespace(): string {
    return 'maniphest';
  }

  public function createFields($object): array {
    $definition = self::getPhlabDefinition();
    if (!$definition) {
      return [];
    }

    return PhabricatorStandardCustomField::buildStandardFields(
      $this,
      [self::RAW_FIELD_KEY => $definition]);
  }

  protected function newStandardEditField() {
    return parent::newStandardEditField()
      ->setCustomFieldCommentAction(new PhlabEditEngineTextCommentAction())
      ->setCommentActionLabel(pht('Change %s', $this->getFieldName()));
  }

  /**
   * The definition this class builds a field from, if it owns the field.
   *
   * Returns nothing if another field already uses this key.
   */
  public static function getPhlabDefinition(): array {
    if (self::getConfiguredDefinition()) {
      return [];
    }

    $definition = (array)PhabricatorEnv::getEnvConfig(
      'phlab.maniphest.status-notes');
    if (!$definition) {
      return [];
    }

    $definition['type'] = 'text';

    return $definition;
  }

  private static function getConfiguredDefinition(): array {
    $definitions = PhabricatorEnv::getEnvConfig(
      'maniphest.custom-field-definitions');

    return (array)idx($definitions, self::RAW_FIELD_KEY, []);
  }

}

<?php

/**
 * The Maniphest "Substatus" field.
 *
 * Phlab owns this field so that it can offer a "Change Substatus" action in
 * the comment area. Core builds comment actions from
 * `PhabricatorCustomField::newCommentAction()`, and the select field which
 * backs a configured field is final and returns null there, so the action can
 * only be attached by a field class we control.
 *
 * The field key is `std:maniphest:general.sub-status.type`, so stored values,
 * the `custom.general.sub-status.type` Conduit key and
 * `PhlabProjectTriggerManiphestSubstatusRule` are all unaffected.
 */
final class PhlabManiphestSubstatusCustomField
  extends ManiphestCustomField
  implements PhabricatorStandardCustomFieldInterface {

  const RAW_FIELD_KEY = 'general.sub-status.type';
  const FIELD_KEY = 'std:maniphest:general.sub-status.type';

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
    $field = parent::newStandardEditField();

    $options = self::getOptions();
    if (!$options) {
      return $field;
    }

    return $field
      ->setCustomFieldCommentAction(
        (new PhabricatorEditEngineSelectCommentAction())
          ->setOptions($options))
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

    $definition = PhabricatorEnv::getEnvConfig('phlab.maniphest.substatus');
    if (!$definition) {
      return [];
    }

    $definition['type'] = 'select';

    return $definition;
  }

  /**
   * The definition wherever it currently lives.
   */
  public static function getDefinition(): array {
    $configured = self::getConfiguredDefinition();
    if ($configured) {
      return $configured;
    }

    return self::getPhlabDefinition();
  }

  public static function getOptions(): array {
    return idx(self::getDefinition(), 'options', []);
  }

  private static function getConfiguredDefinition(): array {
    $definitions = PhabricatorEnv::getEnvConfig(
      'maniphest.custom-field-definitions');

    return idx($definitions, self::RAW_FIELD_KEY, []);
  }

}

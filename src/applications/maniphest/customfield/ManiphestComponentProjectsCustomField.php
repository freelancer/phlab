<?php

/**
 * Custom field for Maniphest tasks that allows selecting component projects.
 *
 * This field ensures that every task is attached to at least one component,
 * making it easier to track and organize work by component.
 */
final class ManiphestComponentProjectsCustomField extends ManiphestCustomField {

  private $value = [];

  public function getFieldKey(): string {
    return 'phlab:component-projects';
  }

  public function getFieldName(): string {
    return pht('Components');
  }

  public function getFieldDescription(): ?string {
    return pht('Select one or more component projects that this task relates to.');
  }

  public function isFieldEnabled(): bool {
    return true;
  }

  public function shouldUseStorage(): bool {
    return false; // Use edge storage instead of custom field storage
  }

  public function shouldAppearInApplicationTransactions(): bool {
    return true;
  }

  protected function newStandardEditField() {
    // For edge-based custom fields, we need to set the EditField's value to a
    // plain array of PHIDs, not the operation format. The operation format is
    // generated later when creating the transaction.
    $field = parent::newStandardEditField();

    // Override the value with the plain PHID array instead of the operation format
    $field->setValue($this->getValue());

    return $field;
  }

  protected function newCommentAction() {
    $viewer = $this->getViewer();

    $subtypes = PhabricatorEnv::getEnvConfig('phlab.projects.component-subtypes');
    $datasource = (new PhlabProjectDatasource())
      ->setParameters(['subtypes' => $subtypes])
      ->setViewer($viewer);

    $action = id(new PhabricatorEditEngineTokenizerCommentAction())
      ->setDatasource($datasource);

    $value = $this->getValue();
    if ($value !== null) {
      $action->setInitialValue($value);
    }

    return $action;
  }

  public function getApplicationTransactionType(): string {
    return PhabricatorTransactions::TYPE_EDGE;
  }

  public function getApplicationTransactionMetadata(): array {
    return [
      'edge:type' => PhabricatorTaskComponentProjectEdgeType::EDGECONST,
    ];
  }

  public function readValueFromObject(PhabricatorCustomFieldInterface $object): void {
    $object_phid = $this->getObject()->getPHID();

    // If this is a new object with no PHID yet, there are no edges to read
    if (!$object_phid) {
      $this->setValue(array());
      return;
    }

    $edges = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $object_phid,
      PhabricatorTaskComponentProjectEdgeType::EDGECONST);

    $this->setValue($edges);
  }

  public function getValueForStorage(): array {
    return ['=' => array_fuse($this->getValue())];
  }

  public function setValueFromStorage($value): void {
    if (is_array($value) && isset($value['='])) {
      $this->setValue(array_values($value['=']));
    } else {
      $this->setValue([]);
    }
  }

  public function getNewValueForApplicationTransactions() {
    // For edge transactions, always return the operation format.
    // This is used when generating the actual transaction.
    // Even when empty, we need the operation format to indicate "set to nothing"
    $value = $this->getValue();
    return array('=' => array_fuse($value));
  }

  public function setValueFromApplicationTransactions($value) {
    // This is called when setting up edit fields.
    // The value can be in different formats:
    // 1. Operation format: ['=' => [...]] - from getValueForStorage
    // 2. Plain array of PHIDs: ['PHID-1', 'PHID-2'] - from UI/requests

    if (!is_array($value)) {
      $this->setValue(array());
      return;
    }

    // Check if this is the operation format
    if (isset($value['='])) {
      $this->setValue(array_values($value['=']));
      return;
    }

    // Otherwise, it's a plain array of PHIDs
    $this->setValue(array_values($value));
  }

  public function readValueFromRequest(AphrontRequest $request): void {
    $this->setValue($request->getArr($this->getFieldKey()));
  }

  public function getRequiredHandlePHIDsForEdit(): array {
    return $this->getValue();
  }

  public function getRequiredHandlePHIDsForPropertyView(): array {
    return $this->getValue();
  }

  public function renderEditControl(array $handles): AphrontFormControl {
    $subtypes = PhabricatorEnv::getEnvConfig('phlab.projects.component-subtypes');
    $datasource = (new PhlabProjectDatasource())
      ->setParameters(['subtypes' => $subtypes]);

    return (new AphrontFormTokenizerControl())
      ->setViewer($this->getViewer())
      ->setLabel($this->getFieldName())
      ->setName($this->getFieldKey())
      ->setValue($this->getValue())
      ->setDatasource($datasource);
  }

  public function renderPropertyViewValue(array $handles) {
    if (!$handles) {
      return null;
    }

    return (new PHUIHandleTagListView())
      ->setHandles($handles);
  }

  public function shouldAppearInPropertyView(): bool {
    return true;
  }

  public function shouldAppearInEditView(): bool {
    return true;
  }

  public function shouldAppearInEditEngine(): bool {
    return true;
  }

  public function isRequired(): bool {
    return false;
  }

  public function getInstructionsForEdit(): ?string {
    return pht('Select at least one component that this task relates to.');
  }

  public function getValue(): array {
    return $this->value;
  }

  public function setValue(array $value): void {
    $this->value = $value;
  }

  public function getFieldValue() {
    return $this->getValue();
  }

  public function shouldAppearInApplicationSearch(): bool {
    return true;
  }

  public function readApplicationSearchValueFromRequest(PhabricatorApplicationSearchEngine $engine, AphrontRequest $request): array {
    $key = $this->getFieldKey();
    return [$key => $request->getArr($key)];
  }

  public function applyApplicationSearchConstraintToQuery(PhabricatorApplicationSearchEngine $engine, PhabricatorCursorPagedPolicyAwareQuery $query, $value): void {
    if (!is_array($value)) {
      return;
    }

    $key = $this->getFieldKey();
    $phids = idx($value, $key, []);

    if (empty($phids)) {
      return;
    }

    // Find all task PHIDs that have relationships with the selected component projects
    $task_phids = [];
    foreach ($phids as $component_phid) {
      $edges = PhabricatorEdgeQuery::loadDestinationPHIDs(
        $component_phid,
        PhabricatorComponentProjectTaskEdgeType::EDGECONST);
      $task_phids = array_merge($task_phids, $edges);
    }

    if (empty($task_phids)) {
      // No tasks found with these components, return empty result
      $query->withPHIDs([]);
      return;
    }

    // Filter the query to only include tasks with the selected components
    $query->withPHIDs($task_phids);
  }

  public function appendToApplicationSearchForm(PhabricatorApplicationSearchEngine $engine, AphrontFormView $form, $value): void {
    $key = $this->getFieldKey();
    $subtypes = PhabricatorEnv::getEnvConfig('phlab.projects.component-subtypes');
    $datasource = (new PhlabProjectDatasource())
      ->setParameters(['subtypes' => $subtypes]);

    $form->appendControl(
      (new AphrontFormTokenizerControl())
        ->setViewer($this->getViewer())
        ->setLabel($this->getFieldName())
        ->setName($key)
        ->setDatasource($datasource)
        ->setValue(idx($value ?: [], $key, [])));
  }

  public function shouldAppearInConduit(): bool {
    return true;
  }

  public function shouldAppearInConduitDictionary(): bool {
    return true;
  }

  public function getConduitDictionaryValue(): array {
    return $this->getValue();
  }

  public function getConduitKey(): string {
    return 'components';
  }

  public function getConduitDescription(): string {
    return pht('Component projects associated with this task.');
  }

  public function shouldAppearInListView(): bool {
    return false;
  }

  public function shouldAppearInDetailView(): bool {
    return false;
  }

  public function renderPropertyViewLabel() {
    return pht('Component projects');
  }

}

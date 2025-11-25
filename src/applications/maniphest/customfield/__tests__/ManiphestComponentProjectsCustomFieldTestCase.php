<?php

/**
 * Test cases for ManiphestComponentProjectsCustomField.
 */
final class ManiphestComponentProjectsCustomFieldTestCase extends PhabricatorTestCase {

  public function testFieldKey(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertEqual('phlab:component-projects', $field->getFieldKey());
  }

  public function testFieldName(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertEqual('Components', $field->getFieldName());
  }

  public function testFieldDescription(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $description = $field->getFieldDescription();
    $this->assertTrue(is_string($description));
    $this->assertTrue(strlen($description) > 0);
  }

  public function testFieldEnabled(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertTrue($field->isFieldEnabled());
  }

  public function testRequired(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertFalse($field->isRequired());
  }

  public function testAppearsInEditView(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertTrue($field->shouldAppearInEditView());
  }

  public function testAppearsInPropertyView(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertTrue($field->shouldAppearInPropertyView());
  }

  public function testAppearsInEditEngine(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertTrue($field->shouldAppearInEditEngine());
  }

  public function testAppearsInApplicationSearch(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertTrue($field->shouldAppearInApplicationSearch());
  }

  public function testAppearsInConduit(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertTrue($field->shouldAppearInConduit());
  }

  public function testConduitKey(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertEqual('components', $field->getConduitKey());
  }

  public function testConduitDescription(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $description = $field->getConduitDescription();
    $this->assertTrue(is_string($description));
    $this->assertTrue(strlen($description) > 0);
  }

  public function testShouldUseStorage(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertFalse($field->shouldUseStorage());
  }

  public function testAppearsInApplicationTransactions(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertTrue($field->shouldAppearInApplicationTransactions());
  }

  public function testApplicationTransactionType(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertEqual(
      PhabricatorTransactions::TYPE_EDGE,
      $field->getApplicationTransactionType());
  }

  public function testApplicationTransactionMetadata(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $metadata = $field->getApplicationTransactionMetadata();
    $this->assertTrue(is_array($metadata));
    $this->assertTrue(isset($metadata['edge:type']));
    $this->assertEqual(
      PhabricatorTaskComponentProjectEdgeType::EDGECONST,
      $metadata['edge:type']);
  }

  public function testValueStorage(): void {
    $field = new ManiphestComponentProjectsCustomField();

    // Test empty value
    $field->setValue([]);
    $storage = $field->getValueForStorage();
    $this->assertTrue(is_array($storage));
    $this->assertTrue(isset($storage['=']));
    $this->assertEqual([], $storage['=']);

    // Test with values
    $test_values = ['PHID-PROJ-123', 'PHID-PROJ-456'];
    $field->setValue($test_values);
    $storage = $field->getValueForStorage();
    $this->assertTrue(is_array($storage));
    $this->assertTrue(isset($storage['=']));
    $this->assertEqual($test_values, array_values($storage['=']));
  }

  public function testValueFromStorage(): void {
    $field = new ManiphestComponentProjectsCustomField();

    // Test empty storage
    $field->setValueFromStorage(['=' => []]);
    $this->assertEqual([], $field->getValue());

    // Test with values
    $test_values = ['PHID-PROJ-123', 'PHID-PROJ-456'];
    $field->setValueFromStorage(['=' => $test_values]);
    $this->assertEqual($test_values, $field->getValue());

    // Test invalid storage format
    $field->setValueFromStorage(null);
    $this->assertEqual([], $field->getValue());
  }

  public function testInstructionsForEdit(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $instructions = $field->getInstructionsForEdit();
    $this->assertTrue(is_string($instructions));
    $this->assertTrue(strlen($instructions) > 0);
  }

  public function testListViewAppearance(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertFalse($field->shouldAppearInListView());
  }

  public function testDetailViewAppearance(): void {
    $field = new ManiphestComponentProjectsCustomField();
    $this->assertFalse($field->shouldAppearInDetailView());
  }

}

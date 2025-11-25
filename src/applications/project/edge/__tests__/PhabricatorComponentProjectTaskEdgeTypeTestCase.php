<?php

/**
 * Test cases for PhabricatorComponentProjectTaskEdgeType.
 */
final class PhabricatorComponentProjectTaskEdgeTypeTestCase extends PhabricatorTestCase {

  public function testEdgeConstant(): void {
    $this->assertEqual(10004, PhabricatorComponentProjectTaskEdgeType::EDGECONST);
  }

  public function testConduitKey(): void {
    $edge_type = new PhabricatorComponentProjectTaskEdgeType();
    $this->assertEqual('component.task', $edge_type->getConduitKey());
  }

  public function testConduitName(): void {
    $edge_type = new PhabricatorComponentProjectTaskEdgeType();
    $name = $edge_type->getConduitName();
    $this->assertTrue(is_string($name));
    $this->assertTrue(strlen($name) > 0);
  }

  public function testConduitDescription(): void {
    $edge_type = new PhabricatorComponentProjectTaskEdgeType();
    $description = $edge_type->getConduitDescription();
    $this->assertTrue(is_string($description));
    $this->assertTrue(strlen($description) > 0);
  }

  public function testInverseEdgeConstant(): void {
    $edge_type = new PhabricatorComponentProjectTaskEdgeType();
    $this->assertEqual(
      PhabricatorTaskComponentProjectEdgeType::EDGECONST,
      $edge_type->getInverseEdgeConstant());
  }

  public function testShouldPreventCycles(): void {
    $edge_type = new PhabricatorComponentProjectTaskEdgeType();
    $this->assertFalse($edge_type->shouldPreventCycles());
  }

  public function testShouldWriteInverseTransactions(): void {
    $edge_type = new PhabricatorComponentProjectTaskEdgeType();
    $this->assertTrue($edge_type->shouldWriteInverseTransactions());
  }

  public function testTransactionStrings(): void {
    $edge_type = new PhabricatorComponentProjectTaskEdgeType();

    // Test add string
    $add_string = $edge_type->getTransactionAddString('admin', 1, 'T123');
    $this->assertTrue(is_string($add_string));
    $this->assertTrue(strlen($add_string) > 0);

    // Test remove string
    $remove_string = $edge_type->getTransactionRemoveString('admin', 1, 'T123');
    $this->assertTrue(is_string($remove_string));
    $this->assertTrue(strlen($remove_string) > 0);

    // Test edit string
    $edit_string = $edge_type->getTransactionEditString('admin', 2, 1, 'T123', 1, 'T456');
    $this->assertTrue(is_string($edit_string));
    $this->assertTrue(strlen($edit_string) > 0);
  }

  public function testFeedStrings(): void {
    $edge_type = new PhabricatorComponentProjectTaskEdgeType();

    // Test feed add string
    $add_string = $edge_type->getFeedAddString('admin', 'P123', 1, 'T123');
    $this->assertTrue(is_string($add_string));
    $this->assertTrue(strlen($add_string) > 0);

    // Test feed remove string
    $remove_string = $edge_type->getFeedRemoveString('admin', 'P123', 1, 'T123');
    $this->assertTrue(is_string($remove_string));
    $this->assertTrue(strlen($remove_string) > 0);

    // Test feed edit string
    $edit_string = $edge_type->getFeedEditString('admin', 'P123', 2, 1, 'T123', 1, 'T456');
    $this->assertTrue(is_string($edit_string));
    $this->assertTrue(strlen($edit_string) > 0);
  }

}

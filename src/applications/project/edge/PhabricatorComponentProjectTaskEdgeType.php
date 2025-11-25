<?php

/**
 * Inverse edge type for relationships between component projects and Maniphest tasks.
 *
 * This is the inverse of PhabricatorTaskComponentProjectEdgeType, allowing
 * component projects to track all associated tasks.
 */
final class PhabricatorComponentProjectTaskEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 10004;

  public function getConduitKey(): string {
    return 'component.task';
  }

  public function getConduitName(): string {
    return pht('Component Task');
  }

  public function getConduitDescription(): string {
    return pht('The component project is related to the task.');
  }

  public function getInverseEdgeConstant(): int {
    return PhabricatorTaskComponentProjectEdgeType::EDGECONST;
  }

  public function shouldPreventCycles(): bool {
    return false;
  }

  public function shouldWriteInverseTransactions(): bool {
    return true;
  }

  public function getTransactionAddString($actor, $add_count, $add_edges) {
    return pht(
      '%s added %s task(s): %s.',
      $actor,
      $add_count,
      $add_edges);
  }

  public function getTransactionRemoveString($actor, $remove_count, $remove_edges) {
    return pht(
      '%s removed %s task(s): %s.',
      $actor,
      $remove_count,
      $remove_edges);
  }

  public function getTransactionEditString($actor, $total_count, $add_count, $add_edges, $remove_count, $remove_edges) {
    return pht(
      '%s edited task(s), added %s: %s; removed %s: %s.',
      $actor,
      $add_count,
      $add_edges,
      $remove_count,
      $remove_edges);
  }

  public function getFeedAddString($actor, $object, $add_count, $add_edges) {
    return pht(
      '%s added %s task(s) for %s: %s.',
      $actor,
      $add_count,
      $object,
      $add_edges);
  }

  public function getFeedRemoveString($actor, $object, $remove_count, $remove_edges) {
    return pht(
      '%s removed %s task(s) for %s: %s.',
      $actor,
      $remove_count,
      $object,
      $remove_edges);
  }

  public function getFeedEditString($actor, $object, $total_count, $add_count, $add_edges, $remove_count, $remove_edges) {
    return pht(
      '%s edited task(s) for %s, added %s: %s; removed %s: %s.',
      $actor,
      $object,
      $add_count,
      $add_edges,
      $remove_count,
      $remove_edges);
  }

}

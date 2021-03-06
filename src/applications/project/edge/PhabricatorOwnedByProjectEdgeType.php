<?php

final class PhabricatorOwnedByProjectEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 10002;

  public function getConduitKey(): string {
    return 'project.owned-by';
  }

  public function getConduitName(): string {
    return pht('Project Owned By');
  }

  public function getConduitDescription(): string {
    return pht('The source project is owned by the team managing the destination project.');
  }

  public function getInverseEdgeConstant(): int {
    return PhabricatorOwnsProjectEdgeType::EDGECONST;
  }

  public function shouldPreventCycles(): bool {
    return true;
  }

  public function shouldWriteInverseTransactions(): bool {
    return true;
  }

  public function getTransactionAddString($actor, $add_count, $add_edges) {
    return pht(
      '%s added %s owner project(s): %s.',
      $actor,
      $add_count,
      $add_edges);
  }

  public function getTransactionRemoveString($actor, $remove_count, $remove_edges) {

    return pht(
      '%s removed %s owner project(s): %s.',
      $actor,
      $remove_count,
      $remove_edges);
  }

  public function getTransactionEditString($actor, $total_count, $add_count, $add_edges, $remove_count, $remove_edges) {
    return pht(
      '%s edited owner project(s), added %s: %s; removed %s: %s.',
      $actor,
      $add_count,
      $add_edges,
      $remove_count,
      $remove_edges);
  }

  public function getFeedAddString($actor, $object, $add_count, $add_edges) {
    return pht(
      '%s added %s owner project(s) for %s: %s.',
      $actor,
      $add_count,
      $object,
      $add_edges);
  }

  public function getFeedRemoveString($actor, $object, $remove_count, $remove_edges) {
    return pht(
      '%s removed %s owner project(s) for %s: %s.',
      $actor,
      $remove_count,
      $object,
      $remove_edges);
  }

  public function getFeedEditString($actor, $object, $total_count, $add_count, $add_edges, $remove_count, $remove_edges) {
    return pht(
      '%s edited owner project(s) for %s, added %s: %s; removed %s: %s.',
      $actor,
      $object,
      $add_count,
      $add_edges,
      $remove_count,
      $remove_edges);
  }

}

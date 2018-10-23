<?php

final class PhabricatorOwnsProjectEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 10001;

  public function getInverseEdgeConstant(): int {
    return PhabricatorOwnedByProjectEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions(): bool {
    return true;
  }

  public function getTransactionAddString($actor, $add_count, $add_edges): PhutilSafeHTML {
    return pht(
      '%s added %s owned project(s): %s.',
      $actor,
      $add_count,
      $add_edges);
  }

  public function getTransactionRemoveString($actor, $remove_count, $remove_edges): PhutilSafeHTML {

    return pht(
      '%s removed %s owned project(s): %s.',
      $actor,
      $remove_count,
      $remove_edges);
  }

  public function getTransactionEditString($actor, $total_count, $add_count, $add_edges, $remove_count, $remove_edges): PhutilSafeHTML {
    return pht(
      '%s edited owned projects(s), added %s: %s; removed %s: %s.',
      $actor,
      $add_count,
      $add_edges,
      $remove_count,
      $remove_edges);
  }

  public function getFeedAddString($actor, $object, $add_count, $add_edges): PhutilSafeHTML {
    return pht(
      '%s added %s owned project(s) for %s: %s.',
      $actor,
      $add_count,
      $object,
      $add_edges);
  }

  public function getFeedRemoveString($actor, $object, $remove_count, $remove_edges): PhutilSafeHTML {
    return pht(
      '%s removed %s owned project(s) for %s: %s.',
      $actor,
      $remove_count,
      $object,
      $remove_edges);
  }

  public function getFeedEditString($actor, $object, $total_count, $add_count, $add_edges, $remove_count, $remove_edges): PhutilSafeHTML {
    return pht(
      '%s edited owned project(s) for %s, added %s: %s; removed %s: %s.',
      $actor,
      $object,
      $add_count,
      $add_edges,
      $remove_count,
      $remove_edges);
  }

}

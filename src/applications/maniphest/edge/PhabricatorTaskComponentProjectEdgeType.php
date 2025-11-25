<?php

/**
 * Edge type for relationships between Maniphest tasks and component projects.
 *
 * This edge type allows tasks to be associated with one or more component
 * projects, enabling better organization and tracking of work by component.
 */
final class PhabricatorTaskComponentProjectEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 10003;

  public function getConduitKey(): string {
    return 'task.component';
  }

  public function getConduitName(): string {
    return pht('Task Component');
  }

  public function getConduitDescription(): string {
    return pht('The task is related to the component project.');
  }

  public function getInverseEdgeConstant(): int {
    return PhabricatorComponentProjectTaskEdgeType::EDGECONST;
  }

  public function shouldPreventCycles(): bool {
    return false;
  }

  public function shouldWriteInverseTransactions(): bool {
    return true;
  }

  private function getCountValue($count) {
    // Handle both PhutilNumber objects and plain integers
    if ($count instanceof PhutilNumber) {
      return $count->getNumber();
    }
    return $count;
  }

  public function getTransactionAddString($actor, $add_count, $add_edges) {
    if ($this->getCountValue($add_count) == 1) {
      return pht(
        '%s added %s component: %s.',
        $actor,
        $add_count,
        $add_edges);
    }
    return pht(
      '%s added %s components: %s.',
      $actor,
      $add_count,
      $add_edges);
  }

  public function getTransactionRemoveString($actor, $remove_count, $remove_edges) {
    if ($this->getCountValue($remove_count) == 1) {
      return pht(
        '%s removed %s component: %s.',
        $actor,
        $remove_count,
        $remove_edges);
    }
    return pht(
      '%s removed %s components: %s.',
      $actor,
      $remove_count,
      $remove_edges);
  }

  public function getTransactionEditString($actor, $total_count, $add_count, $add_edges, $remove_count, $remove_edges) {
    if ($this->getCountValue($total_count) == 1) {
      return pht(
        '%s edited component, added %s: %s; removed %s: %s.',
        $actor,
        $add_count,
        $add_edges,
        $remove_count,
        $remove_edges);
    }
    return pht(
      '%s edited components, added %s: %s; removed %s: %s.',
      $actor,
      $add_count,
      $add_edges,
      $remove_count,
      $remove_edges);
  }

  public function getFeedAddString($actor, $object, $add_count, $add_edges) {
    if ($this->getCountValue($add_count) == 1) {
      return pht(
        '%s added %s component for %s: %s.',
        $actor,
        $add_count,
        $object,
        $add_edges);
    }
    return pht(
      '%s added %s components for %s: %s.',
      $actor,
      $add_count,
      $object,
      $add_edges);
  }

  public function getFeedRemoveString($actor, $object, $remove_count, $remove_edges) {
    if ($this->getCountValue($remove_count) == 1) {
      return pht(
        '%s removed %s component for %s: %s.',
        $actor,
        $remove_count,
        $object,
        $remove_edges);
    }
    return pht(
      '%s removed %s components for %s: %s.',
      $actor,
      $remove_count,
      $object,
      $remove_edges);
  }

  public function getFeedEditString($actor, $object, $total_count, $add_count, $add_edges, $remove_count, $remove_edges) {
    if ($this->getCountValue($total_count) == 1) {
      return pht(
        '%s edited component for %s, added %s: %s; removed %s: %s.',
        $actor,
        $object,
        $add_count,
        $add_edges,
        $remove_count,
        $remove_edges);
    }
    return pht(
      '%s edited components for %s, added %s: %s; removed %s: %s.',
      $actor,
      $object,
      $add_count,
      $add_edges,
      $remove_count,
      $remove_edges);
  }

}

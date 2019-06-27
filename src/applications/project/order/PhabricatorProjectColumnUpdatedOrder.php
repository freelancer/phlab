<?php

final class PhabricatorProjectColumnUpdatedOrder
  extends PhabricatorProjectColumnOrder {

  const ORDERKEY = 'updated';

  protected function newMenuIconIcon(): string {
    return 'fa-clock-o';
  }

  public function getDisplayName(): string {
    return pht('Sort by Updated Date');
  }

  public function getHasHeaders(): bool {
    return false;
  }

  public function getCanReorder(): bool {
    return false;
  }

  public function getMenuOrder(): int {
    return 5000;
  }

  protected function newSortVectorForObject($object): array {
    return [
      -1 * (int)$object->getDateModified(),
      -1 * (int)$object->getID(),
    ];
  }

}

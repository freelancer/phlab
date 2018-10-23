<?php

final class PhabricatorOwnedByProjectEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 10002;

  public function getInverseEdgeConstant(): int {
    return PhabricatorOwnsProjectEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions(): bool {
    return true;
  }

}

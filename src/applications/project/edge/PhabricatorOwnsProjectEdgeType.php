<?php

final class PhabricatorOwnsProjectEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 10001;

  public function getInverseEdgeConstant(): int {
    return PhabricatorOwnedByProjectEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions(): bool {
    return true;
  }

}

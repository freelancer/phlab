<?php

final class DifferentialMakeDraftHeraldAction
  extends HeraldAction {

  const ACTIONCONST = 'differential.revision.make-draft';
  const DO_MAKE_DRAFT = 'revision.make-draft';

  public function getHeraldActionName() {
    return pht('Make revision draft');
  }

  public function renderActionDescription($value) {
    return pht(
      'Put revision in draft state.');
  }

  protected function getActionEffectMap() {
    return array(
      self::DO_MAKE_DRAFT => array(
        'icon' => 'fa-headphones',
        'color' => 'red',
        'name' => pht('Make draft'),
      ),
    );
  }

  protected function renderActionEffectDescription($type, $data) {
    switch ($type) {
      case self::DO_MAKE_DRAFT:
        return pht(
          'Put revision in draft state.');
    }
  }


  public function supportsObject($object) {
    return ($object instanceof DifferentialRevision);
  }

  public function supportsRuleType($rule_type) {
    return $rule_type == HeraldRuleTypeConfig::RULE_TYPE_GLOBAL;
  }

  public function getHeraldActionStandardType() {
    return self::STANDARD_NONE;
  }

  public function applyEffect($object, HeraldEffect $effect) {
    if (
      $object->isDraft() ||
      !($object->isNeedsReview() || $object->isChangePlanned()) ||
      !$this->isValidEffect($object)
    ) {
        return;
    }

    $object
      ->setModernRevisionStatus(DifferentialRevisionStatus::DRAFT)
      ->setShouldBroadcast(false)
      ->setHoldAsDraft(true)
      ->save();
  }


  public function isValidEffect($revision) {
    $adapter = $this->getAdapter();
    $viewer = $adapter->getViewer();
    $xactions = $adapter->getAppliedTransactions();

    if (count($xactions) > 1) {
      // if it entered this block, it means that there are more than one transaction
      // done to the object with one click/command
      // i.e.
      // comment + request review
      // comment + request review + change reviewers
      $request_actions = array_filter($xactions, function ($xaction) {
          return ($xaction->getTransactionType() == DifferentialRevisionRequestReviewTransaction::TRANSACTIONTYPE);
      });

      if ($request_actions) {
          return false;
      }

      // or maybe we're updating a diff which is update + comment
      $update_actions = array_filter($xactions, function ($xaction) {
          return ($xaction->getTransactionType() == DifferentialRevisionUpdateTransaction::TRANSACTIONTYPE);
      });

      if ($update_actions) {
        return true;
      }
    }

    $xactions = id(new DifferentialTransactionQuery())
        ->setViewer($viewer)
        ->withObjectPHIDs(array($revision->getPHID()))
        ->setOrder('newest')
        ->setLimit(20)
        ->execute();

    // the logic below is for new diffs and we should ignore it if
    // the transaction query returned 20 transactions which is our set limit
    if (count($xactions) < 20) {
      $request_actions = array_filter($xactions, function ($xaction) {
          return ($xaction->getTransactionType() == DifferentialRevisionRequestReviewTransaction::TRANSACTIONTYPE);
      });

      $draft_actions = array_filter($xactions, function ($xaction) {
          return ($xaction->getTransactionType() == DifferentialRevisionHoldDraftTransaction::TRANSACTIONTYPE);
      });

      $review_request_transaction_count = count($request_actions);
      $draft_transaction_count = count($draft_actions);

      $request_review_from_draft_state = $draft_transaction_count === 1;

      // revisions created from `--draft` flag have no review request transaction
      // meanwhile, revisions created with `--only` have one review request transaction
      // i.e.
      // it's not valid if it's a draft that's being published
      // it's valid if it's a revision being created for the first time
      if ($review_request_transaction_count === 1) {
          return !$request_review_from_draft_state;
      }
    }

    foreach ($xactions as $xaction) {
        // we need to check if there's a transaction like `request-review`
        // that caused the diff to be in `needs-review` status.
        if ($xaction->getTransactionType() == DifferentialRevisionRequestReviewTransaction::TRANSACTIONTYPE) {
            return false;
        }
        // however, if there's a more recent update, allow `draft` to take effect
        if ($xaction->getTransactionType() == DifferentialRevisionUpdateTransaction::TRANSACTIONTYPE) {
            return true;
        }
    }

    return false;
  }
}

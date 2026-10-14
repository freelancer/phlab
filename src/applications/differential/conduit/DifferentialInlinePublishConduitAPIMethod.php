<?php

final class DifferentialInlinePublishConduitAPIMethod
  extends DifferentialInlineConduitAPIMethod {

  public function getAPIMethodName() {
    return 'differential.inline.publish';
  }

  public function getMethodDescription() {
    return pht(
      'Publish your draft inline comments on a Differential revision, with '.
      'an optional top-level comment, as one review: the same as pressing '.
      '"Submit" in the web UI. Also publishes any pending "Done" changes '.
      'you made as the revision author. Pass "inlineIDs" to publish only '.
      'those drafts; otherwise every draft you have on the revision is '.
      'published, including ones started in a browser.'.
      "\n\n".
      'Publishing needs "Can Edit" on the revision, as submitting inline '.
      'comments does in the web UI; without it the call fails with '.
      '"You Shall Not Pass". If a call times out, search before retrying: '.
      'an inline that was published has "isDraft": false, and naming it '.
      'again fails with ERR-BAD-INLINE.');
  }

  protected function defineParamTypes() {
    return array(
      'revisionID' => 'required revisionid',
      'inlineIDs' => 'optional list<int>',
      'message' => 'optional string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR-BAD-REVISION' => pht(
        'No such revision, or you cannot see it.'),
      'ERR-BAD-INLINE' => pht(
        'An ID in "inlineIDs" is not one of your unpublished drafts on this '.
        'revision.'),
      'ERR-NOTHING-TO-PUBLISH' => pht(
        'You have no drafts or pending "Done" changes on this revision, '.
        'and no "message" was given.'),
    );
  }

  protected function newRevisionQuery() {
    // The revision editor requires these attached.
    return id(new DifferentialRevisionQuery())
      ->needActiveDiffs(true)
      ->needReviewers(true)
      ->needReviewerAuthority(true);
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $revision = $this->loadRevision(
      $viewer,
      self::getRequiredTypedParameter(
        new ConduitIntParameterType(),
        $request,
        'revisionID'));

    $editor = id(new DifferentialTransactionEditor())
      ->setActor($viewer)
      ->setContentSource($request->newContentSource())
      ->setContinueOnNoEffect(true)
      ->setContinueOnMissingFields(true);

    // The web comment form's path (DifferentialRevisionEditEngine::
    // newAutomaticCommentTransactions()): one transaction per publishable
    // draft, plus one for pending "Done" changes.
    $xactions = $editor->newAutomaticInlineTransactions(
      $revision,
      DifferentialTransaction::TYPE_INLINE,
      new DifferentialDiffInlineCommentQuery());

    $inline_ids = self::getTypedParameter(
      new ConduitIntListParameterType(),
      $request,
      'inlineIDs');
    if ($inline_ids !== null) {
      $xactions = $this->filterInlineTransactions($xactions, $inline_ids);
    }

    $message = (string)self::getTypedParameter(
      new ConduitStringParameterType(),
      $request,
      'message');
    if (strlen(trim($message))) {
      $xactions[] = id(new DifferentialTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
        ->attachComment(
          id(new DifferentialTransactionComment())
            ->setContent($message));
    }

    if (!self::hasAnyEffect($xactions)) {
      throw new ConduitException('ERR-NOTHING-TO-PUBLISH');
    }

    $applied = $editor->applyTransactions($revision, $xactions);

    self::synchronizeDraftFlag($viewer, $revision);

    $type_inline = DifferentialTransaction::TYPE_INLINE;

    $published = array();
    foreach ($applied as $xaction) {
      if ($xaction->getTransactionType() == $type_inline) {
        $published[] = (int)$xaction->getComment()->getID();
      }
    }
    sort($published);

    return array(
      'revisionID' => (int)$revision->getID(),
      'inlineIDs' => $published,
      'transactionPHIDs' => array_values(mpull($applied, 'getPHID')),
    );
  }

  private function filterInlineTransactions(
    array $xactions,
    array $inline_ids) {

    $type_inline = DifferentialTransaction::TYPE_INLINE;
    $wanted = array_fuse($inline_ids);

    $kept = array();
    $skipped = array();
    foreach ($xactions as $xaction) {
      if ($xaction->getTransactionType() != $type_inline) {
        $kept[] = $xaction;
        continue;
      }

      $comment = $xaction->getComment();
      $id = (int)$comment->getID();
      if (isset($wanted[$id])) {
        $kept[] = $xaction;
        unset($wanted[$id]);
      } else {
        $skipped[$comment->getPHID()] = true;
      }
    }

    if ($wanted) {
      throw id(new ConduitException('ERR-BAD-INLINE'))
        ->setErrorDescription(
          pht(
            'Not your unpublished drafts on this revision: %s.',
            implode(', ', array_keys($wanted))));
    }

    // The pending "Done" transaction covers every draft, so take the
    // skipped ones out of it, or their "Done" state would be finalized while
    // they stay unpublished. Keep it even when that empties it: without one,
    // the editor builds a fresh one that covers every draft again.
    $type_state = PhabricatorTransactions::TYPE_INLINESTATE;
    foreach ($kept as $xaction) {
      if ($xaction->getTransactionType() != $type_state) {
        continue;
      }

      $details = $xaction->getMetadataValue('inline.details', array());
      $xaction
        ->setOldValue(array_diff_key($xaction->getOldValue(), $skipped))
        ->setNewValue(array_diff_key($xaction->getNewValue(), $skipped))
        ->setMetadataValue(
          'inline.details',
          array_diff_key($details, $skipped));
    }

    return $kept;
  }

  /**
   * False when the only transaction left is an emptied "Done" one.
   */
  private static function hasAnyEffect(array $xactions) {
    $type_state = PhabricatorTransactions::TYPE_INLINESTATE;

    foreach ($xactions as $xaction) {
      if ($xaction->getTransactionType() != $type_state) {
        return true;
      }

      if ($xaction->getNewValue()) {
        return true;
      }
    }

    return false;
  }

}

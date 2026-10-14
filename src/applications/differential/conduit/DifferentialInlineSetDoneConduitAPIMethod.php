<?php

final class DifferentialInlineSetDoneConduitAPIMethod
  extends DifferentialInlineConduitAPIMethod {

  public function getAPIMethodName() {
    return 'differential.inline.setdone';
  }

  public function getMethodDescription() {
    return pht(
      'Check or uncheck "Done" on a Differential inline comment, with the '.
      'same permission rule as the web UI: the revision author, or the '.
      'comment author while the comment is still a draft. Repeating a call '.
      'changes nothing. A check by the revision author is pending '.
      '("isDonePending": true) until they next submit, for example with '.
      '"differential.inline.publish".');
  }

  protected function defineParamTypes() {
    return array(
      'inlineID' => 'required int',
      'isDone' => 'required bool',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR-BAD-INLINE' => pht(
        'No such inline comment, or you cannot see it.'),
      'ERR-NOT-EDITABLE' => pht(
        'Only the revision author, or the comment author while the comment '.
        'is a draft, can mark it done.'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $inline_id = self::getRequiredTypedParameter(
      new ConduitIntParameterType(),
      $request,
      'inlineID');
    $want_done = self::getRequiredTypedParameter(
      new ConduitBoolParameterType(),
      $request,
      'isDone');

    $bad_inline = id(new ConduitException('ERR-BAD-INLINE'))
      ->setErrorDescription(
        pht(
          'Inline comment %d does not exist, or you cannot see it.',
          $inline_id));

    $comment = self::loadVisibleObject(
      self::newVisibleInlineQuery($viewer)
        ->withIDs(array($inline_id)));
    if (!$comment) {
      throw $bad_inline;
    }

    // Comment storage is world-readable, so the revision must be visible
    // before anything about the comment is revealed. The changeset query
    // loads its diff and revision as the viewer, and drops the changeset if
    // the viewer cannot see them.
    $changeset = self::loadVisibleObject(
      id(new DifferentialChangesetQuery())
        ->setViewer($viewer)
        ->withIDs(array($comment->getChangesetID())));
    $revision = $changeset ? $changeset->getDiff()->getRevision() : null;
    if (!$revision || $revision->getPHID() !== $comment->getRevisionPHID()) {
      throw $bad_inline;
    }

    $inline = $comment->newInlineCommentObject();

    $viewer_phid = $viewer->getPHID();
    $is_owner = ($viewer_phid === $revision->getAuthorPHID());
    $is_author = ($viewer_phid === $inline->getAuthorPHID());
    if (!$is_owner && !($is_author && $inline->isDraft())) {
      throw id(new ConduitException('ERR-NOT-EDITABLE'))
        ->setErrorDescription(
          pht(
            'Only the revision author, or the comment author while the '.
            'comment is a draft, can mark inline comment %d done.',
            $inline_id));
    }

    $fixed_state = DifferentialInlineCommentRules::normalizeFixedState(
      $inline->getFixedState());
    $next_state = DifferentialInlineCommentRules::getNextFixedState(
      $fixed_state,
      $want_done);
    if ($next_state !== $fixed_state) {
      $inline->setFixedState($next_state)->save();
    }

    return $this->buildInlineDictionary($revision, $inline, $changeset);
  }

}

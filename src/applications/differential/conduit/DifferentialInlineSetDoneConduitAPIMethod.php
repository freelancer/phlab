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

    list($revision, $changeset, $inline) = self::loadVisibleInline(
      $viewer,
      $inline_id);

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

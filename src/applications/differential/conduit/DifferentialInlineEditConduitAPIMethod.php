<?php

final class DifferentialInlineEditConduitAPIMethod
  extends DifferentialInlineConduitAPIMethod {

  public function getAPIMethodName() {
    return 'differential.inline.edit';
  }

  public function getMethodDescription() {
    return pht(
      'Replace the text and suggested edit of one of your draft inline '.
      'comments, as editing it in the web UI does. Only the comment author '.
      'can, and only while it is a draft.'.
      "\n\n".
      '"content" and "suggestionText" work as in '.
      '"differential.inline.create", and replace what the draft had: '.
      'leaving out "suggestionText" removes its suggested edit. Its place '.
      'in the diff does not change.');
  }

  protected function defineParamTypes() {
    return array(
      'inlineID' => 'required int',
      'content' => 'required string',
      'suggestionText' => 'optional string',
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
        'Only the comment author can edit it, and only while it is a draft.'),
      'ERR-BAD-SUGGESTION' => pht(
        'A suggested edit needs the new side of a diff uploaded with full '.
        'context, and cannot go on a reply or in a document view.'),
      'ERR-EMPTY-COMMENT' => pht(
        'Provide non-empty "content" or "suggestionText". A suggestion that '.
        'deletes the lines ("suggestionText": "") needs "content".'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $inline_id = self::getRequiredTypedParameter(
      new ConduitIntParameterType(),
      $request,
      'inlineID');
    list($content, $suggestion_text) = self::readCommentContent($request);

    list($revision, $changeset, $inline) = self::loadVisibleInline(
      $viewer,
      $inline_id);
    self::assertCanChangeDraft($viewer, $inline);

    if ($suggestion_text !== null) {
      $this->validateSuggestion($viewer, $changeset, $inline);
    }

    // Keep the old suggestion text when there is no new one, as the web UI
    // does when "Suggest Edit" is unchecked, so checking it again in the
    // browser starts from it.
    $old_state = $inline->getContentState();
    $state = id(new PhabricatorDiffInlineCommentContentState())
      ->setContentText($content)
      ->setContentHasSuggestion($suggestion_text !== null)
      ->setContentSuggestionText(
        coalesce($suggestion_text, $old_state->getContentSuggestionText()));

    $inline->setContentState($state);

    // Phabricator before upstream rP6fd55d692f has no committed state (see
    // `differential.inline.create`).
    if (method_exists($inline, 'setCommittedContentState')) {
      $inline->setCommittedContentState($state);
    }

    self::saveDraftChange($viewer, $revision, $inline);

    return $this->buildInlineDictionary($revision, $inline, $changeset);
  }

  private function validateSuggestion(
    PhabricatorUser $viewer,
    DifferentialChangeset $changeset,
    DifferentialInlineComment $inline) {

    if (!$inline->getIsNewFile()) {
      throw id(new ConduitException('ERR-BAD-SUGGESTION'))
        ->setErrorDescription(
          pht('Suggested edits can only be made on the new side.'));
    }

    // The web UI never offers a suggestion on a reply, or on a comment made
    // in a document view such as an image or a notebook
    // (`PhabricatorInlineCommentController::newInlineResponse()`).
    if ($inline->getReplyToCommentPHID()) {
      throw id(new ConduitException('ERR-BAD-SUGGESTION'))
        ->setErrorDescription(
          pht('Suggested edits cannot be made on a reply.'));
    }

    if ($inline->getDocumentEngineKey() !== null) {
      throw id(new ConduitException('ERR-BAD-SUGGESTION'))
        ->setErrorDescription(
          pht(
            'Inline comment %d was made in a document view, which cannot '.
            'show a suggested edit.',
            $inline->getID()));
    }

    $hunks = id(new DifferentialHunkQuery())
      ->setViewer($viewer)
      ->withChangesets(array($changeset))
      ->execute();

    if (!DifferentialInlineCommentRules::canRenderSuggestion($hunks)) {
      throw id(new ConduitException('ERR-BAD-SUGGESTION'))
        ->setErrorDescription(
          pht(
            'Diff %d was uploaded without full context for "%s", so the '.
            'web UI cannot show a suggested edit on it.',
            $changeset->getDiffID(),
            $changeset->getFilename()));
    }
  }

}

<?php

/**
 * Shared loading, visibility and serialization for the `differential.inline.*`
 * methods.
 *
 * Inline comment storage is saved with a "public" view policy (see
 * `DifferentialInlineComment::getTransactionCommentForSave()`), so the comment
 * query never enforces policy. Every method loads the revision as the viewer
 * before it reads or writes a comment on it.
 */
abstract class DifferentialInlineConduitAPIMethod
  extends DifferentialConduitAPIMethod {

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodStatusDescription() {
    return pht(
      'Freelancer extension (phlab). The shape may change before it is '.
      'marked stable.');
  }

  /**
   * The query `loadRevision()` starts from. A method that needs more
   * attached (the active diff, reviewers) overrides this, so the others do
   * not pay for it.
   */
  protected function newRevisionQuery() {
    return new DifferentialRevisionQuery();
  }

  final protected function loadRevision(
    PhabricatorUser $viewer,
    $revision_id) {

    $revision = self::loadVisibleObject(
      $this->newRevisionQuery()
        ->setViewer($viewer)
        ->withIDs(array($revision_id)));
    if (!$revision) {
      throw id(new ConduitException('ERR-BAD-REVISION'))
        ->setErrorDescription(
          pht(
            'Revision "%s" does not exist, or you cannot see it.',
            $revision_id));
    }

    return $revision;
  }

  /**
   * Like `executeOne()`, except an object the viewer cannot see comes back
   * as null, the same as one that does not exist, instead of as a policy
   * exception that confirms it exists.
   */
  final protected static function loadVisibleObject(
    PhabricatorPolicyAwareQuery $query) {

    $objects = $query->execute();
    if (!$objects) {
      return null;
    }

    return head($objects);
  }

  /**
   * Read a parameter with Conduit's typed parser, as the "*.search" and
   * "*.edit" methods do. The "bool" and "int" strings in
   * `defineParamTypes()` are documentation only and never enforced, so a
   * plain HTTP call's "false" would otherwise read as true.
   */
  final protected static function getTypedParameter(
    ConduitParameterType $type,
    ConduitAPIRequest $request,
    $key) {

    $value = $request->getValue($key);
    if ($value === null) {
      return null;
    }

    return $type->getValue(
      array($key => $value),
      $key,
      $request->getIsStrictlyTyped());
  }

  /**
   * Like `getTypedParameter()`, for a parameter the call cannot do without.
   * `defineParamTypes()` documents "required" but Conduit does not enforce
   * it.
   */
  final protected static function getRequiredTypedParameter(
    ConduitParameterType $type,
    ConduitAPIRequest $request,
    $key) {

    $value = self::getTypedParameter($type, $request, $key);
    if ($value === null) {
      throw new Exception(pht('Parameter "%s" is required.', $key));
    }

    return $value;
  }

  /**
   * The path a changeset goes by on one side of the diff. The old side of a
   * moved file is named by its old path, so renames round-trip between
   * `*.search` and `*.create`.
   */
  final protected static function getChangesetSidePath(
    DifferentialChangeset $changeset,
    $is_new) {

    if ($is_new) {
      return $changeset->getFilename();
    }

    return nonempty(
      $changeset->getOldFile(),
      $changeset->getFilename());
  }

  /**
   * Inline comments the viewer can see: every published one, plus the
   * viewer's own undeleted drafts. This is the revision page's rule
   * (`DifferentialRevisionViewController`), so other users' drafts never
   * appear.
   */
  final protected static function newVisibleInlineQuery(
    PhabricatorUser $viewer) {

    return id(new DifferentialDiffInlineCommentQuery())
      ->setViewer($viewer)
      ->withPublishedComments(true)
      ->withPublishableComments(true);
  }

  /**
   * The revision, changeset and inline comment for `$inline_id`, if the
   * viewer can see the comment. One they cannot see fails exactly like one
   * that does not exist.
   */
  final protected static function loadVisibleInline(
    PhabricatorUser $viewer,
    $inline_id) {

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

    return array($revision, $changeset, $comment->newInlineCommentObject());
  }

  /**
   * The web UI's rule for changing or deleting an inline comment
   * (`DifferentialInlineCommentEditController::canEditInlineComment()`):
   * only its author may, and only while it is a draft.
   */
  final protected static function assertCanChangeDraft(
    PhabricatorUser $viewer,
    DifferentialInlineComment $inline) {

    $is_author = ($inline->getAuthorPHID() === $viewer->getPHID());
    if ($is_author && $inline->isDraft()) {
      return;
    }

    throw id(new ConduitException('ERR-NOT-EDITABLE'))
      ->setErrorDescription(
        pht(
          'Only the author of inline comment %d can change it, and only '.
          'while it is a draft.',
          $inline->getID()));
  }

  /**
   * "content" and "suggestionText", as `differential.inline.create` and
   * `differential.inline.edit` take them.
   */
  final protected static function readCommentContent(
    ConduitAPIRequest $request) {

    $string_type = new ConduitStringParameterType();

    $content = self::getRequiredTypedParameter(
      $string_type,
      $request,
      'content');
    $suggestion_text = self::getTypedParameter(
      $string_type,
      $request,
      'suggestionText');

    // Without text or replacement lines the inline is "void": upstream
    // hides it everywhere and never publishes it.
    if (!strlen(trim($content)) && !strlen($suggestion_text)) {
      throw new ConduitException('ERR-EMPTY-COMMENT');
    }

    // A suggestion replaces whole lines. Without a final newline the web
    // UI joins it to the line after the comment.
    if (strlen($suggestion_text) && substr($suggestion_text, -1) !== "\n") {
      $suggestion_text .= "\n";
    }

    return array($content, $suggestion_text);
  }

  /**
   * Save a change to an existing draft as the web UI does
   * (`PhabricatorInlineCommentController::saveComment()`): discard the
   * author's unsaved browser edits of it, and keep the revision's draft
   * marker in step.
   */
  final protected static function saveDraftChange(
    PhabricatorUser $viewer,
    DifferentialRevision $revision,
    DifferentialInlineComment $inline) {

    // As the web UI's "save" does. While a comment is marked as being edited,
    // the text a browser tab still holds for it is loaded over the saved text,
    // including when it is published.
    $inline->setIsEditing(false);

    $inline->openTransaction();
      $inline->save();
      PhabricatorVersionedDraft::purgeDrafts(
        $inline->getPHID(),
        $viewer->getPHID());
      self::synchronizeDraftFlag($viewer, $revision);
    $inline->saveTransaction();
  }

  /**
   * As the web UI does after saving or submitting inlines: keep the
   * revision's "you have drafts" marker for this user in step, so it shows
   * in their revision lists and the "unsubmitted comments" prompt.
   */
  final protected static function synchronizeDraftFlag(
    PhabricatorUser $viewer,
    DifferentialRevision $revision) {

    $revision->newDraftEngine()
      ->setObject($revision)
      ->setViewer($viewer)
      ->synchronize();
  }

  /**
   * One inline in the `*.search` result shape, so a client that already
   * pages `differential.revision.search` can page this too.
   */
  final protected function buildInlineDictionary(
    DifferentialRevision $revision,
    DifferentialInlineComment $inline,
    DifferentialChangeset $changeset) {

    // "isNewFile" means the right-hand (new) side of the diff, not an added
    // file.
    $is_new = (bool)$inline->getIsNewFile();

    $state = $inline->getContentState();
    if ($state->getContentHasSuggestion()) {
      $suggestion_text = (string)$state->getContentSuggestionText();
    } else {
      $suggestion_text = null;
    }

    $fixed_state = DifferentialInlineCommentRules::normalizeFixedState(
      $inline->getFixedState());

    $phid = $inline->getPHID();

    return array(
      'id' => (int)$inline->getID(),
      'type' => phid_get_type($phid),
      'phid' => $phid,
      'fields' => array(
        'revisionID' => (int)$revision->getID(),
        'revisionPHID' => $revision->getPHID(),
        'diffID' => (int)$changeset->getDiffID(),
        'changesetID' => (int)$changeset->getID(),
        'authorPHID' => $inline->getAuthorPHID(),
        'filePath' => self::getChangesetSidePath($changeset, $is_new),
        'isNewFile' => $is_new,
        'lineNumber' => (int)$inline->getLineNumber(),
        'lineLength' => (int)$inline->getLineLength(),
        'startOffset' => self::toNullableInt($inline->getStartOffset()),
        'endOffset' => self::toNullableInt($inline->getEndOffset()),
        'content' => (string)$inline->getContent(),
        'suggestionText' => $suggestion_text,
        'isDraft' => $inline->isDraft(),
        'isDone' => DifferentialInlineCommentRules::isCheckedState(
          $fixed_state),
        'isDonePending' => DifferentialInlineCommentRules::isPendingState(
          $fixed_state),
        'fixedState' => $fixed_state,
        'replyToCommentPHID' => $inline->getReplyToCommentPHID(),
        'dateCreated' => (int)$inline->getDateCreated(),
        'dateModified' => (int)$inline->getDateModified(),
      ),
    );
  }

  private static function toNullableInt($value) {
    if ($value === null) {
      return null;
    }
    return (int)$value;
  }

}

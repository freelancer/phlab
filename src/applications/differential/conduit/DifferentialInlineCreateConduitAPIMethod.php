<?php

final class DifferentialInlineCreateConduitAPIMethod
  extends DifferentialInlineConduitAPIMethod {

  public function getAPIMethodName() {
    return 'differential.inline.create';
  }

  public function getMethodDescription() {
    return pht(
      'Create a draft inline comment on a Differential revision, optionally '.
      'highlighting a character range and attaching a suggested edit. '.
      'Drafts are visible only to you until you call '.
      '"differential.inline.publish".'.
      "\n\n".
      '"isNewFile" true comments on the right-hand (new) side of the diff. '.
      '"lineNumber" is the first line; "lineLength" is how many more lines '.
      'the comment spans, so 0 means one line.'.
      "\n\n".
      '"startOffset" is a 0-based column on the first commented line and '.
      '"endOffset" an exclusive column on the last one, counted on the line '.
      'as the web UI shows it: JavaScript string units, with each tab drawn '.
      'as up to 2 columns. On a single line, "endOffset" must be greater '.
      'than "startOffset"; across lines it may be smaller.'.
      "\n\n".
      '"suggestionText" replaces the commented lines on the new side (a '.
      'final newline is added if missing; "" deletes the lines and then '.
      'needs "content"). It needs a diff uploaded with full context, as '.
      '"arc diff" does.'.
      "\n\n".
      'To reply to a published inline comment, as "Reply" does in the web '.
      'UI, pass its "replyToInlineID" or "replyToInlinePHID" (the "id" or '.
      '"phid" from "differential.inline.search") and "content". The reply '.
      'goes on the same diff, side and lines as that comment, so it stays '.
      'threaded with it whichever diff is shown; "revisionID", "diffID", '.
      '"filePath", "isNewFile", "lineNumber" and "lineLength" may be left '.
      'out, and any given must match the comment. A reply cannot have '.
      '"startOffset", "endOffset" or "suggestionText", as in the web UI. It '.
      'is a draft like any other until published.');
  }

  protected function defineParamTypes() {
    return array(
      'revisionID' => 'optional revisionid',
      'diffID' => 'optional diffid',
      'filePath' => 'optional string (required unless replying)',
      'isNewFile' => 'optional bool (required unless replying)',
      'lineNumber' => 'optional int (required unless replying)',
      'lineLength' => 'optional int',
      'startOffset' => 'optional int',
      'endOffset' => 'optional int',
      'content' => 'required string',
      'suggestionText' => 'optional string',
      'replyToInlineID' => 'optional int',
      'replyToInlinePHID' => 'optional phid',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR-NEED-DIFF' => pht(
        'Provide "revisionID", "diffID", or both.'),
      'ERR-BAD-REVISION' => pht(
        'No such revision, or you cannot see it.'),
      'ERR-BAD-DIFF' => pht(
        'No such diff, you cannot see it, or it does not belong to the '.
        'revision.'),
      'ERR-BAD-FILE' => pht(
        'The file is not part of this diff.'),
      'ERR-BAD-LINE' => pht(
        'The lines are not in the diff on that side.'),
      'ERR-BAD-OFFSETS' => pht(
        'Provide both "startOffset" and "endOffset" or neither, inside the '.
        'first and last commented lines.'),
      'ERR-BAD-SUGGESTION' => pht(
        'A suggested edit needs the new side of a diff uploaded with full '.
        'context, and cannot go on a reply.'),
      'ERR-BAD-REPLY' => pht(
        'The comment to reply to does not exist, you cannot see it, it is '.
        'still a draft, or the reply names a different revision, diff, '.
        'place or range from it.'),
      'ERR-EMPTY-COMMENT' => pht(
        'Provide non-empty "content" or "suggestionText". A suggestion that '.
        'deletes the lines ("suggestionText": "") needs "content".'),
    );
  }

  protected function newRevisionQuery() {
    return id(new DifferentialRevisionQuery())
      ->needActiveDiffs(true);
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $parent = $this->loadReplyParent($request);
    if ($parent) {
      return $this->createReply($request, $parent);
    }

    $bool_type = new ConduitBoolParameterType();
    $int_type = new ConduitIntParameterType();
    $string_type = new ConduitStringParameterType();

    $path = self::getRequiredTypedParameter(
      $string_type,
      $request,
      'filePath');
    $is_new = self::getRequiredTypedParameter(
      $bool_type,
      $request,
      'isNewFile');
    $line = self::getRequiredTypedParameter(
      $int_type,
      $request,
      'lineNumber');
    $length = coalesce(
      self::getTypedParameter($int_type, $request, 'lineLength'),
      0);
    $start_offset = self::getTypedParameter($int_type, $request, 'startOffset');
    $end_offset = self::getTypedParameter($int_type, $request, 'endOffset');
    list($content, $suggestion_text) = self::readCommentContent($request);

    list($revision, $diff) = $this->loadRevisionAndDiff($request);
    $changeset = $this->loadChangeset($viewer, $diff, $path, $is_new);
    $hunks = $changeset->getHunks();

    $lines = $this->validatePlacement(
      $diff,
      $hunks,
      $path,
      $is_new,
      $line,
      $length,
      $start_offset,
      $end_offset,
      $suggestion_text);

    $default_suggestion = self::getDefaultSuggestion($hunks, $lines);

    $initial_state = id(new PhabricatorDiffInlineCommentContentState())
      ->setContentSuggestionText($default_suggestion);

    $state = id(new PhabricatorDiffInlineCommentContentState())
      ->setContentText($content)
      ->setContentHasSuggestion($suggestion_text !== null)
      ->setContentSuggestionText(
        coalesce($suggestion_text, $default_suggestion));

    $inline = id(new DifferentialInlineComment())
      ->setRevision($revision)
      ->setChangesetID($changeset->getID())
      ->setIsNewFile((int)$is_new)
      ->setLineNumber($line)
      ->setLineLength($length)
      ->setStartOffset($start_offset)
      ->setEndOffset($end_offset);

    self::saveNewInline($viewer, $revision, $inline, $state, $initial_state);

    return $this->buildInlineDictionary($revision, $inline, $changeset);
  }

  /**
   * The content states the web UI writes when an inline is created and then
   * saved carry the commented lines as the default suggestion, so "Suggest
   * Edit" in the browser starts from them. `$lines` is null when the lines
   * are not in the diff.
   */
  private static function getDefaultSuggestion(array $hunks, $lines) {
    if ($lines === null) {
      return '';
    }

    if (!DifferentialInlineCommentRules::canRenderSuggestion($hunks)) {
      return '';
    }

    return implode('', $lines);
  }

  /**
   * Save a new draft as the web UI's "new" and "reply" operations do
   * (`PhabricatorInlineCommentController`), with its content states, Done
   * state and the revision's draft marker.
   */
  private static function saveNewInline(
    PhabricatorUser $viewer,
    DifferentialRevision $revision,
    DifferentialInlineComment $inline,
    PhabricatorDiffInlineCommentContentState $state,
    PhabricatorDiffInlineCommentContentState $initial_state) {

    $inline
      ->setAuthorPHID($viewer->getPHID())
      ->setContentState($state);

    // Phabricator before upstream rP6fd55d692f has no initial or committed
    // state, and its web UI writes only the content state. Production may run
    // such a version (see `bin/production-versions`), so check.
    if (method_exists($inline, 'setInitialContentState')) {
      $inline
        ->setInitialContentState($initial_state)
        ->setCommittedContentState($state);
    }

    // As the web UI does for a new inline or reply: the revision author's own
    // comments start out checked "Done", pending until they submit.
    if ($viewer->getPHID() === $revision->getAuthorPHID()) {
      $inline->setFixedState(PhabricatorInlineComment::STATE_DRAFT);
    }

    $inline->openTransaction();
      $inline->save();
      self::synchronizeDraftFlag($viewer, $revision);
    $inline->saveTransaction();
  }

  /**
   * The revision, changeset and comment that "replyToInlineID" or
   * "replyToInlinePHID" names, or null when neither is given.
   *
   * The web UI offers "Reply" only on a comment you cannot edit
   * (`PHUIDiffInlineCommentDetailView`, `$can_reply`), so never on a draft:
   * your own drafts show "Edit" instead, and nobody else's are visible.
   */
  private function loadReplyParent(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $parent_id = self::getTypedParameter(
      new ConduitIntParameterType(),
      $request,
      'replyToInlineID');
    $parent_phid = self::getTypedParameter(
      new ConduitPHIDParameterType(),
      $request,
      'replyToInlinePHID');

    if ($parent_id === null && $parent_phid === null) {
      return null;
    }

    if ($parent_id !== null && $parent_phid !== null) {
      throw id(new ConduitException('ERR-BAD-REPLY'))
        ->setErrorDescription(
          pht('Pass "replyToInlineID" or "replyToInlinePHID", not both.'));
    }

    $query = self::newVisibleInlineQuery($viewer);
    if ($parent_id !== null) {
      $query->withIDs(array($parent_id));
      $name = $parent_id;
    } else {
      $query->withPHIDs(array($parent_phid));
      $name = $parent_phid;
    }

    $parent = self::findVisibleInline($viewer, $query);
    if (!$parent) {
      throw id(new ConduitException('ERR-BAD-REPLY'))
        ->setErrorDescription(
          pht(
            'Inline comment "%s" does not exist, or you cannot see it.',
            $name));
    }

    // The visible query has already left out deleted drafts and other users'
    // drafts, and a published comment cannot be deleted.
    list($revision, $changeset, $inline) = $parent;
    if ($inline->isDraft()) {
      throw id(new ConduitException('ERR-BAD-REPLY'))
        ->setErrorDescription(
          pht(
            'Inline comment %d is your unpublished draft, which the web UI '.
            'offers to edit rather than reply to. Change it with '.
            '"differential.inline.edit", or publish it first.',
            $inline->getID()));
    }

    return $parent;
  }

  /**
   * A draft reply to `$parent`, as the web UI's "Reply" makes it
   * (`PhabricatorInlineCommentController::readRequestParameters()`): on the
   * parent's changeset, side and lines, even when that is an older diff, so
   * the two stay together whichever diff is shown (upstream T10562).
   */
  private function createReply(ConduitAPIRequest $request, array $parent) {
    $viewer = $request->getUser();
    list($revision, $changeset, $parent_inline) = $parent;

    list($content, $suggestion_text) = self::readCommentContent($request);

    if ($suggestion_text !== null) {
      throw self::newSuggestionOnReplyException();
    }

    $this->validateReplyPlacement(
      $request,
      $revision,
      $changeset,
      $parent_inline);

    $inline = id(new DifferentialInlineComment())
      ->setRevision($revision)
      ->setChangesetID($changeset->getID())
      ->setIsNewFile((int)$parent_inline->getIsNewFile())
      ->setLineNumber($parent_inline->getLineNumber())
      ->setLineLength($parent_inline->getLineLength())
      ->setStartOffset(null)
      ->setEndOffset(null)
      ->setReplyToCommentPHID($parent_inline->getPHID());

    // A reply to a comment made in a document view (an image, a notebook)
    // is made in that view too.
    $engine_key = $parent_inline->getDocumentEngineKey();
    if ($engine_key !== null) {
      $inline->setDocumentEngineKey($engine_key);
    }

    // The states upstream's web reply stores, which are those of any new
    // inline: no suggestion, with the commented lines as its default text.
    $hunks = id(new DifferentialHunkQuery())
      ->setViewer($viewer)
      ->withChangesets(array($changeset))
      ->execute();
    $lines = DifferentialInlineCommentRules::getCommentedLines(
      $hunks,
      (bool)$parent_inline->getIsNewFile(),
      $parent_inline->getLineNumber(),
      $parent_inline->getLineLength());
    $default_suggestion = self::getDefaultSuggestion($hunks, $lines);

    $initial_state = id(new PhabricatorDiffInlineCommentContentState())
      ->setContentSuggestionText($default_suggestion);

    $state = id(new PhabricatorDiffInlineCommentContentState())
      ->setContentText($content)
      ->setContentSuggestionText($default_suggestion);

    self::saveNewInline(
      $viewer,
      $revision,
      $inline,
      $state,
      $initial_state);

    return $this->buildInlineDictionary($revision, $inline, $changeset);
  }

  /**
   * A reply's place comes from its parent. Placement parameters may still be
   * sent, e.g. copied from the parent's search result, but must agree with
   * it, so a caller who meant somewhere else finds out.
   */
  private function validateReplyPlacement(
    ConduitAPIRequest $request,
    DifferentialRevision $revision,
    DifferentialChangeset $changeset,
    DifferentialInlineComment $parent) {

    $int_type = new ConduitIntParameterType();
    $is_new = (bool)$parent->getIsNewFile();

    $expected = array(
      'revisionID' => array($int_type, (int)$revision->getID()),
      'diffID' => array($int_type, (int)$changeset->getDiffID()),
      'filePath' => array(
        new ConduitStringParameterType(),
        self::getChangesetSidePath($changeset, $is_new),
      ),
      'isNewFile' => array(new ConduitBoolParameterType(), $is_new),
      'lineNumber' => array($int_type, (int)$parent->getLineNumber()),
      'lineLength' => array($int_type, (int)$parent->getLineLength()),
    );

    // The old side of a moved file goes by its old path, but a new comment
    // there may also name it by its current one (`getPathCandidates()`).
    $also_accepted = array(
      'filePath' => $changeset->getFilename(),
    );

    foreach ($expected as $key => $spec) {
      list($type, $value) = $spec;
      $given = self::getTypedParameter($type, $request, $key);
      if ($given === null || $given === $value) {
        continue;
      }
      if (!$is_new && $given === idx($also_accepted, $key)) {
        continue;
      }

      throw id(new ConduitException('ERR-BAD-REPLY'))
        ->setErrorDescription(
          pht(
            'A reply goes where inline comment %d is: line %d (+%d) on the '.
            '%s side of "%s", in diff %d of D%d. "%s" there is %s, not %s; '.
            'leave it out.',
            $parent->getID(),
            $parent->getLineNumber(),
            $parent->getLineLength(),
            $is_new ? pht('new') : pht('old'),
            self::getChangesetSidePath($changeset, $is_new),
            $changeset->getDiffID(),
            $revision->getID(),
            $key,
            phutil_var_export($value),
            phutil_var_export($given)));
    }

    // The web UI's reply carries no character range (`DiffInline.js`,
    // `bindToReply()` copies none).
    foreach (array('startOffset', 'endOffset') as $key) {
      if (self::getTypedParameter($int_type, $request, $key) !== null) {
        throw id(new ConduitException('ERR-BAD-REPLY'))
          ->setErrorDescription(
            pht(
              'A reply cannot highlight a character range; leave out "%s".',
              $key));
      }
    }
  }

  /**
   * Check the comment sits where the web UI could have put it, and return
   * the commented source lines.
   */
  private function validatePlacement(
    DifferentialDiff $diff,
    array $hunks,
    $path,
    $is_new,
    $line,
    $length,
    $start_offset,
    $end_offset,
    $suggestion_text) {

    $lines = DifferentialInlineCommentRules::getCommentedLines(
      $hunks,
      $is_new,
      $line,
      $length);
    if ($lines === null) {
      throw id(new ConduitException('ERR-BAD-LINE'))
        ->setErrorDescription(
          pht(
            'Lines %d-%d on the %s side of "%s" are not in diff %d.',
            $line,
            $line + $length,
            $is_new ? pht('new') : pht('old'),
            $path,
            $diff->getID()));
    }

    $is_valid_range = DifferentialInlineCommentRules::isValidOffsetRange(
      $lines,
      $start_offset,
      $end_offset);
    if (!$is_valid_range) {
      throw id(new ConduitException('ERR-BAD-OFFSETS'))
        ->setErrorDescription(
          pht(
            'Offsets %s-%s do not fit lines %d-%d of "%s".',
            phutil_var_export($start_offset),
            phutil_var_export($end_offset),
            $line,
            $line + $length,
            $path));
    }

    if ($suggestion_text === null) {
      return $lines;
    }

    if (!$is_new) {
      throw id(new ConduitException('ERR-BAD-SUGGESTION'))
        ->setErrorDescription(
          pht('Suggested edits can only be made on the new side.'));
    }

    if (!DifferentialInlineCommentRules::canRenderSuggestion($hunks)) {
      throw id(new ConduitException('ERR-BAD-SUGGESTION'))
        ->setErrorDescription(
          pht(
            'Diff %d was uploaded without full context for "%s", so the '.
            'web UI cannot show a suggested edit on it.',
            $diff->getID(),
            $path));
    }

    return $lines;
  }

  private function loadRevisionAndDiff(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $int_type = new ConduitIntParameterType();
    $revision_id = self::getTypedParameter($int_type, $request, 'revisionID');
    $diff_id = self::getTypedParameter($int_type, $request, 'diffID');

    if (!$revision_id && !$diff_id) {
      throw new ConduitException('ERR-NEED-DIFF');
    }

    if (!$diff_id) {
      $revision = $this->loadRevision($viewer, $revision_id);
      return array($revision, $revision->getActiveDiff());
    }

    $diff = self::loadVisibleObject(
      id(new DifferentialDiffQuery())
        ->setViewer($viewer)
        ->withIDs(array($diff_id)));

    // The diff query loads and attaches the revision as the viewer, and
    // drops a diff whose revision the viewer cannot see. A diff that was
    // never attached to a revision has nothing attached at all.
    $revision = null;
    if ($diff && $diff->getRevisionID()) {
      $revision = $diff->getRevision();
    }

    $is_bad_diff =
      !$revision ||
      ($revision_id && ((int)$revision->getID() !== $revision_id));
    if ($is_bad_diff) {
      throw id(new ConduitException('ERR-BAD-DIFF'))
        ->setErrorDescription(
          pht(
            'Diff "%s" does not exist, you cannot see it, or it is not '.
            'attached to the revision.',
            $diff_id));
    }

    return array($revision, $diff);
  }

  private function loadChangeset(
    PhabricatorUser $viewer,
    DifferentialDiff $diff,
    $path,
    $is_new) {

    $changesets = id(new DifferentialChangesetQuery())
      ->setViewer($viewer)
      ->withDiffs(array($diff))
      ->execute();

    $candidates = self::getPathCandidates($changesets, $path, $is_new);
    if (!$candidates) {
      throw id(new ConduitException('ERR-BAD-FILE'))
        ->setErrorDescription(
          pht(
            'File "%s" is not part of diff %d.',
            $path,
            $diff->getID()));
    }

    // Hunks for the candidate files only; a large diff has many files.
    id(new DifferentialHunkQuery())
      ->setViewer($viewer)
      ->withChangesets($candidates)
      ->needAttachToChangesets(true)
      ->execute();

    // Prefer a changeset that carries lines for this side, and among those
    // the file itself over a copy of it.
    foreach ($candidates as $candidate) {
      if ($candidate->getHunks()) {
        return $candidate;
      }
    }

    return head($candidates);
  }

  /**
   * Changesets whose `$is_new` side is `$path`, most specific first.
   *
   * The old side of a moved or copied file is named by its old path, so it
   * matches the copy as well as the file itself; the file itself comes
   * first. A move leaves a "moved away" marker with no lines, which never
   * matches. When nothing has `$path` as its old side, the old side also
   * accepts the file's current path.
   */
  private static function getPathCandidates(
    array $changesets,
    $path,
    $is_new) {

    // A copy source keeps its own lines (TYPE_COPY_AWAY is a modified file),
    // so only these two are markers.
    $markers = array(
      DifferentialChangeType::TYPE_MOVE_AWAY => true,
      DifferentialChangeType::TYPE_MULTICOPY => true,
    );

    $same_name = array();
    $old_name = array();
    foreach ($changesets as $changeset) {
      $is_same_name = ($changeset->getFilename() === $path);

      if ($is_new) {
        if ($is_same_name) {
          $same_name[] = $changeset;
        }
        continue;
      }

      $is_marker = isset($markers[$changeset->getChangeType()]);
      $is_old_side =
        !$is_marker &&
        (self::getChangesetSidePath($changeset, false) === $path);
      if ($is_old_side) {
        if ($is_same_name) {
          $same_name[] = $changeset;
        } else {
          $old_name[] = $changeset;
        }
      }
    }

    $candidates = array_merge($same_name, $old_name);
    if ($candidates || $is_new) {
      return $candidates;
    }

    return self::getPathCandidates($changesets, $path, true);
  }

}

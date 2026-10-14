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
      '"arc diff" does.');
  }

  protected function defineParamTypes() {
    return array(
      'revisionID' => 'optional revisionid',
      'diffID' => 'optional diffid',
      'filePath' => 'required string',
      'isNewFile' => 'required bool',
      'lineNumber' => 'required int',
      'lineLength' => 'optional int',
      'startOffset' => 'optional int',
      'endOffset' => 'optional int',
      'content' => 'required string',
      'suggestionText' => 'optional string',
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
        'context.'),
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

    // The content states the web UI writes when an inline is created and
    // then saved. The initial state carries the commented lines as the
    // default suggestion, so "Suggest Edit" in the browser starts from them.
    if (DifferentialInlineCommentRules::canRenderSuggestion($hunks)) {
      $default_suggestion = implode('', $lines);
    } else {
      $default_suggestion = '';
    }

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
      ->setAuthorPHID($viewer->getPHID())
      ->setIsNewFile((int)$is_new)
      ->setLineNumber($line)
      ->setLineLength($length)
      ->setStartOffset($start_offset)
      ->setEndOffset($end_offset)
      ->setContentState($state);

    // Phabricator before upstream rP6fd55d692f has no initial or committed
    // state, and its web UI writes only the content state. Production may run
    // such a version (see `bin/production-versions`), so check.
    if (method_exists($inline, 'setInitialContentState')) {
      $inline
        ->setInitialContentState($initial_state)
        ->setCommittedContentState($state);
    }

    // As the web UI does for a new inline: the revision author's own
    // comments start out checked "Done", pending until they submit.
    if ($viewer->getPHID() === $revision->getAuthorPHID()) {
      $inline->setFixedState(PhabricatorInlineComment::STATE_DRAFT);
    }

    $inline->openTransaction();
      $inline->save();
      self::synchronizeDraftFlag($viewer, $revision);
    $inline->saveTransaction();

    return $this->buildInlineDictionary($revision, $inline, $changeset);
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

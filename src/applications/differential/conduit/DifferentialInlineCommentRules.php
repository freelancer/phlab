<?php

/**
 * Where an inline comment may be placed, and how "Done" moves, as pure
 * functions so they can be tested without storage. The rules restate what
 * the web UI already allows; each one names the upstream code it follows.
 */
final class DifferentialInlineCommentRules extends Phobject {

  /**
   * The source lines `$line` to `$line + $length` on one side of a
   * changeset, or null when any of them falls outside the hunks the diff
   * carries (a diff uploaded with full context has one hunk covering the
   * whole file).
   *
   * @param list<DifferentialHunk> $hunks
   * @return list<string>|null Lines, each with its trailing newline.
   */
  public static function getCommentedLines(
    array $hunks,
    $is_new,
    $line,
    $length) {

    if ($line < 1 || $length < 0) {
      return null;
    }

    foreach ($hunks as $hunk) {
      if ($is_new) {
        $first = (int)$hunk->getNewOffset();
        $count = (int)$hunk->getNewLen();
      } else {
        $first = (int)$hunk->getOldOffset();
        $count = (int)$hunk->getOldLen();
      }

      if ($line < $first || ($line + $length) > ($first + $count - 1)) {
        continue;
      }

      // Only the matching hunk's text is built: a large hunk may be stored
      // in a file and has to be read and parsed.
      if ($is_new) {
        $text = $hunk->makeNewFile();
      } else {
        $text = $hunk->makeOldFile();
      }

      return array_slice(
        phutil_split_lines($text),
        $line - $first,
        $length + 1);
    }

    return null;
  }

  /**
   * Are these character offsets a highlight the web UI could have made?
   *
   * `startOffset` is a column on the first commented line and `endOffset` a
   * column on the last one (`DiffChangesetList.js`, `_newHoverMap()`), so on
   * a multi-line range the end may be smaller than the start. Columns are
   * counted on the rendered line, in JavaScript string units, with tabs
   * expanded to two-column stops (`DifferentialChangesetParser`). The bound
   * used here is the line's UTF-16 length plus one per tab, which every
   * rendering fits inside, so a caller counting raw characters is never
   * wrongly rejected.
   *
   * @param list<string> $lines From `getCommentedLines()`.
   */
  public static function isValidOffsetRange(
    array $lines,
    $start_offset,
    $end_offset) {

    if ($start_offset === null && $end_offset === null) {
      return true;
    }

    if ($start_offset === null || $end_offset === null) {
      return false;
    }

    if ($start_offset < 0 || $end_offset < 0) {
      return false;
    }

    if ($start_offset > self::getMaximumColumn(head($lines))) {
      return false;
    }

    if ($end_offset > self::getMaximumColumn(last($lines))) {
      return false;
    }

    if (count($lines) === 1 && $end_offset <= $start_offset) {
      return false;
    }

    return true;
  }

  /**
   * Can the web UI render a suggested edit on this changeset? It needs the
   * inline's source context, which upstream only builds for a single hunk
   * starting at the top of both files, i.e. a full-context diff.
   *
   * @see DifferentialDiffInlineCommentQuery::newInlineContextMap()
   * @param list<DifferentialHunk> $hunks
   */
  public static function canRenderSuggestion(array $hunks) {
    if (count($hunks) !== 1) {
      return false;
    }

    $hunk = head($hunks);

    return
      ((int)$hunk->getOldOffset() <= 1) &&
      ((int)$hunk->getNewOffset() <= 1);
  }

  /**
   * A comment that was never marked has no fixed state; it reads as
   * `undone`.
   */
  public static function normalizeFixedState($fixed_state) {
    return nonempty($fixed_state, PhabricatorInlineComment::STATE_UNDONE);
  }

  /**
   * Is "Done" shown as checked? `draft` and `undraft` are the revision
   * author's pending check and uncheck. The next time the author submits,
   * upstream's inline state map turns them into `done` and `undone`.
   *
   * @see PhabricatorApplicationTransactionEditor::newInlineStateTransaction()
   */
  public static function isCheckedState($fixed_state) {
    $map = PhabricatorTransactions::getInlineStateMap();
    $final_state = idx($map, $fixed_state, $fixed_state);

    return ($final_state === PhabricatorInlineComment::STATE_DONE);
  }

  public static function isPendingState($fixed_state) {
    $map = PhabricatorTransactions::getInlineStateMap();

    return isset($map[$fixed_state]);
  }

  /**
   * The fixed state that makes "Done" read as `$want_done`. This is the web
   * UI's toggle expressed as a target, so repeating a call changes nothing.
   * Unchecking a pending check cancels it (`draft` -> `undone`) instead of
   * recording a pending uncheck, and the reverse for `undraft`.
   *
   * @see PhabricatorInlineCommentController::processRequest(), "done"
   */
  public static function getNextFixedState($fixed_state, $want_done) {
    $fixed_state = self::normalizeFixedState($fixed_state);

    if (self::isCheckedState($fixed_state) === (bool)$want_done) {
      return $fixed_state;
    }

    switch ($fixed_state) {
      case PhabricatorInlineComment::STATE_DRAFT:
        return PhabricatorInlineComment::STATE_UNDONE;
      case PhabricatorInlineComment::STATE_UNDRAFT:
        return PhabricatorInlineComment::STATE_DONE;
      case PhabricatorInlineComment::STATE_DONE:
        return PhabricatorInlineComment::STATE_UNDRAFT;
      default:
        return PhabricatorInlineComment::STATE_DRAFT;
    }
  }

  private static function getMaximumColumn($line) {
    $line = rtrim($line, "\r\n");

    $width = 0;
    foreach (phutil_utf8v(phutil_utf8ize($line)) as $character) {
      // Characters outside the Basic Multilingual Plane are 4 bytes in
      // UTF-8 and 2 units in a JavaScript string.
      $width += (strlen($character) === 4) ? 2 : 1;
    }

    return $width + substr_count($line, "\t");
  }

}

<?php

final class DifferentialInlineCommentRulesTestCase extends PhutilTestCase {

  public function testGetCommentedLinesWithFullContext(): void {
    $hunks = [
      $this->newHunk(1, 4, 1, 4, implode('', [
        " one\n",
        "-two\n",
        "+TWO\n",
        " three\n",
        "-four\n",
        "+FOUR\n",
      ])),
    ];

    $this->assertEqual(
      ["TWO\n", "three\n"],
      DifferentialInlineCommentRules::getCommentedLines($hunks, true, 2, 1));
    $this->assertEqual(
      ["two\n"],
      DifferentialInlineCommentRules::getCommentedLines($hunks, false, 2, 0));

    $outside = [
      'line 0' => [0, 0],
      'past the end' => [5, 0],
      'range runs past the end' => [3, 2],
      'negative length' => [2, -1],
    ];
    foreach ($outside as $label => $spec) {
      $this->assertEqual(
        null,
        DifferentialInlineCommentRules::getCommentedLines(
          $hunks,
          true,
          $spec[0],
          $spec[1]),
        $label);
    }
  }

  public function testGetCommentedLinesWithPartialContext(): void {
    // Two hunks, as "git diff -U1" uploads them: lines 4-6 and 10-12 exist,
    // 7-9 do not.
    $hunks = [
      $this->newHunk(4, 3, 4, 3, " a\n-b\n+B\n c\n"),
      $this->newHunk(10, 3, 10, 3, " d\n-e\n+E\n f\n"),
    ];

    $this->assertEqual(
      ["E\n"],
      DifferentialInlineCommentRules::getCommentedLines($hunks, true, 11, 0));
    $this->assertEqual(
      null,
      DifferentialInlineCommentRules::getCommentedLines($hunks, true, 8, 0),
      'line between hunks');
    $this->assertEqual(
      null,
      DifferentialInlineCommentRules::getCommentedLines($hunks, true, 6, 4),
      'range spanning two hunks');
  }

  public function testIsValidOffsetRange(): void {
    $one = ["\treturn \$x;\n"];
    $two = ["if (\$x == \$y) {\n", "  return;\n"];

    $cases = [
      'no range' => [$one, null, null, true],
      'single line' => [$one, 1, 8, true],
      'single line to the end, tab counted wide' => [$one, 0, 12, true],
      'past the end of the line' => [$one, 0, 13, false],
      'only start' => [$one, 1, null, false],
      'only end' => [$one, null, 4, false],
      'negative' => [$one, -1, 4, false],
      'empty' => [$one, 4, 4, false],
      'backwards on one line' => [$one, 6, 2, false],
      'multi-line, end column before start column' => [$two, 10, 3, true],
      'multi-line, start past first line' => [$two, 17, 3, false],
      'multi-line, end past last line' => [$two, 0, 10, false],
    ];

    foreach ($cases as $label => $case) {
      list($lines, $start, $end, $expect) = $case;
      $this->assertEqual(
        $expect,
        DifferentialInlineCommentRules::isValidOffsetRange(
          $lines,
          $start,
          $end),
        $label);
    }
  }

  public function testIsValidOffsetRangeCountsJavaScriptUnits(): void {
    // A 4-byte character is one PHP character but two JavaScript units.
    $lines = ["\xF0\x9F\x98\x80ok\n"];

    $this->assertTrue(
      DifferentialInlineCommentRules::isValidOffsetRange($lines, 0, 4));
    $this->assertFalse(
      DifferentialInlineCommentRules::isValidOffsetRange($lines, 0, 5));
  }

  public function testCanRenderSuggestion(): void {
    $full = [$this->newHunk(1, 3, 1, 3, " a\n-b\n+B\n c\n")];
    $added = [$this->newHunk(0, 0, 1, 1, "+a\n")];
    $offset = [$this->newHunk(4, 3, 4, 3, " a\n-b\n+B\n c\n")];
    $two = [
      $this->newHunk(1, 1, 1, 1, "-a\n+A\n"),
      $this->newHunk(9, 1, 9, 1, "-b\n+B\n"),
    ];

    $cases = [
      'one hunk from the top' => [$full, true],
      'added file' => [$added, true],
      'one hunk further down' => [$offset, false],
      'two hunks' => [$two, false],
      'no hunks' => [[], false],
    ];

    foreach ($cases as $label => $case) {
      list($hunks, $expect) = $case;
      $this->assertEqual(
        $expect,
        DifferentialInlineCommentRules::canRenderSuggestion($hunks),
        $label);
    }
  }

  public function testGetNextFixedState(): void {
    $undone = PhabricatorInlineComment::STATE_UNDONE;
    $draft = PhabricatorInlineComment::STATE_DRAFT;
    $undraft = PhabricatorInlineComment::STATE_UNDRAFT;
    $done = PhabricatorInlineComment::STATE_DONE;

    // [current, want done, expected] -- the web UI's "done" toggle, as a
    // target. A pending check or uncheck is cancelled, not stacked.
    $cases = [
      [null, true, $draft],
      [null, false, $undone],
      [$undone, true, $draft],
      [$undone, false, $undone],
      [$draft, true, $draft],
      [$draft, false, $undone],
      [$undraft, true, $done],
      [$undraft, false, $undraft],
      [$done, true, $done],
      [$done, false, $undraft],
    ];

    foreach ($cases as $case) {
      list($current, $want_done, $expect) = $case;
      $next = DifferentialInlineCommentRules::getNextFixedState(
        $current,
        $want_done);
      $label = pht('%s, want done: %s', $current, $want_done ? 'yes' : 'no');

      $this->assertEqual($expect, $next, $label);
      $this->assertEqual(
        $want_done,
        DifferentialInlineCommentRules::isCheckedState($next),
        $label);
      $this->assertEqual(
        $next,
        DifferentialInlineCommentRules::getNextFixedState($next, $want_done),
        pht('%s, repeated', $label));
    }
  }

  private function newHunk(
    int $old_offset,
    int $old_len,
    int $new_offset,
    int $new_len,
    string $changes): DifferentialHunk {

    return id(new DifferentialHunk())
      ->setOldOffset($old_offset)
      ->setOldLen($old_len)
      ->setNewOffset($new_offset)
      ->setNewLen($new_len)
      ->setChanges($changes);
  }

}

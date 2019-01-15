<?php

final class PhlabRemarkupEngineTestCase extends PhutilTestCase {

  public function testEngine(): void {
    $root = __DIR__.'/remarkup/';

    $files = id(new FileFinder($root))
      ->withType('f')
      ->withSuffix('txt')
      ->find();

    foreach ($files as $file) {
      $this->markupText($file, $root.$file);
    }
  }

  private function markupText(string $name, string $path): void {
    $contents = Filesystem::readFile($path);
    $contents = explode("\n~~~~~~~~~~\n", $contents);

    if (count($contents) < 3) {
      throw new Exception(pht('Malformed test case.'));
    }

    list($input, $expected_html, $expected_text) = $contents;
    $engine = $this->buildNewTestEngine();

    $this->assertEqual(
      $expected_html,
      (string)$engine->markupText($input),
      pht("Failed to markup HTML in file '%s'.", $name));

    $engine->setMode(PhutilRemarkupEngine::MODE_TEXT);
    $this->assertEqual(
      $expected_text,
      (string)$engine->markupText($input),
      pht("Failed to markup text in file '%s'.", $name));
  }

  /**
   * Build a Remarkup engine.
   *
   * NOTE: Most of this method is copied from
   * @{method:PhutilRemarkupEngineTestCase:buildNewTestEngine}.
   */
  private function buildNewTestEngine(): PhutilRemarkupEngine {
    $engine = new PhutilRemarkupEngine();

    $engine->setConfig('preserve-linebreaks', true);

    $rules = [
      new PhutilRemarkupEscapeRemarkupRule(),
      new PhutilRemarkupMonospaceRule(),
      new PhutilRemarkupDocumentLinkRule(),
      new PhutilRemarkupHyperlinkRule(),
      new PhutilRemarkupBoldRule(),
      new PhutilRemarkupItalicRule(),
      new PhutilRemarkupDelRule(),
      new PhutilRemarkupUnderlineRule(),
      new PhutilRemarkupHighlightRule(),
      new PhabricatorNewLineRemarkupRule(),
    ];

    $blocks = [
      new PhutilRemarkupQuotesBlockRule(),
      new PhutilRemarkupReplyBlockRule(),
      new PhutilRemarkupHeaderBlockRule(),
      new PhutilRemarkupHorizontalRuleBlockRule(),
      new PhutilRemarkupCodeBlockRule(),
      new PhutilRemarkupLiteralBlockRule(),
      new PhutilRemarkupNoteBlockRule(),
      new PhutilRemarkupTableBlockRule(),
      new PhutilRemarkupSimpleTableBlockRule(),
      new PhutilRemarkupDefaultBlockRule(),
      new PhutilRemarkupListBlockRule(),
      new PhutilRemarkupInterpreterBlockRule(),
    ];

    foreach ($blocks as $block) {
      if (!$block instanceof PhutilRemarkupCodeBlockRule) {
        $block->setMarkupRules($rules);
      }
    }

    $engine->setBlockRules($blocks);
    return $engine;
  }

}

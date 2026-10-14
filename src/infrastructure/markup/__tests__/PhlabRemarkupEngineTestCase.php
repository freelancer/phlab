<?php

final class PhlabRemarkupEngineTestCase extends PhutilTestCase {

  public function testEngine(): void {
    $root = __DIR__.'/remarkup/';

    $files = (new FileFinder($root))
      ->withType('f')
      ->withSuffix('txt')
      ->find();

    foreach ($files as $file) {
      $this->markupText($root.$file);
    }
  }

  private function markupText(string $path): void {
    $name = basename($path);

    $contents = Filesystem::readFile($path);
    $contents = explode("\n~~~~~~~~~~\n", $contents);

    if (count($contents) < 3) {
      throw new Exception(
        pht(
          "Expected '%s' separating test case, ".
          "rendered HTML and rendered text in file '%s'.",
          '~~~~~~~~~~',
          $name));
    } else {
      list($input, $expected_html, $expected_text) = $contents;
    }

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
   * Build a Remarkup engine with the core rules and Phlab's own.
   */
  private function buildNewTestEngine(): PhutilRemarkupEngine {
    $engine = new PhutilRemarkupEngine();
    $engine->setConfig('preserve-linebreaks', true);

    $rules = array_merge(
      $this->loadRules(
        'phabricator',
        'infrastructure/markup/markuprule/',
        PhutilRemarkupRule::class),
      $this->loadRules(
        phutil_get_current_library_name(),
        null,
        PhutilRemarkupRule::class));

    $blocks = array_merge(
      $this->loadRules(
        'phabricator',
        'infrastructure/markup/blockrule/',
        PhutilRemarkupBlockRule::class),
      $this->loadRules(
        phutil_get_current_library_name(),
        null,
        PhutilRemarkupBlockRule::class));

    foreach ($blocks as $block) {
      // Code blocks are rendered literally, with no markup rules.
      if (!$block instanceof PhutilRemarkupCodeBlockRule) {
        $block->setMarkupRules($rules);
      }
    }

    $engine->setBlockRules($blocks);
    return $engine;
  }

  /**
   * Load the concrete subclasses of `$ancestor` in `$library`.
   *
   * The core rules, once in libphutil, are now in Phabricator under
   * `infrastructure/markup/`, beside Phabricator's own rules, which this test
   * leaves out. We have to use `PhutilSymbolLoader` instead of
   * `PhutilClassMapQuery` because the latter can't filter by library or path.
   * The engine sorts the rules by priority.
   */
  private function loadRules(
    string $library,
    ?string $path_prefix,
    string $ancestor): array {

    return (new PhutilSymbolLoader())
      ->setType('class')
      ->setLibrary($library)
      ->setPathPrefix($path_prefix)
      ->setAncestorClass($ancestor)
      ->setConcreteOnly(true)
      ->loadObjects();
  }

}

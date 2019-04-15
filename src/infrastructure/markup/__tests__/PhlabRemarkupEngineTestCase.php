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
   * Build a Remarkup engine.
   */
  private function buildNewTestEngine(): PhutilRemarkupEngine {
    $engine = new PhutilRemarkupEngine();
    $engine->setConfig('preserve-linebreaks', true);

    // NOTE: We have to use `PhutilSymbolLoader` instead of
    // `PhutilClassMapQuery` because the latter doesn't have a
    // `setLibrary` method.
    $current_library = phutil_get_current_library_name();

    $phutil_rules = (new PhutilSymbolLoader())
      ->setType('class')
      ->setLibrary('phutil')
      ->setAncestorClass(PhutilRemarkupRule::class)
      ->setConcreteOnly(true)
      ->loadObjects();
    $phlab_rules = (new PhutilSymbolLoader())
      ->setType('class')
      ->setLibrary($current_library)
      ->setAncestorClass(PhutilRemarkupRule::class)
      ->setConcreteOnly(true)
      ->loadObjects();

    // Merge rules from `phutil` and `phlab`.
    $rules = array_merge($phutil_rules, $phlab_rules);

    // Order `$rules` by priority.
    usort(
      $rules,
      function (PhutilRemarkupRule $a, PhutilRemarkupRule $b): bool {
        return $a->getPriority() <=> $b->getPriority();
      });

    $phutil_blocks = (new PhutilSymbolLoader())
      ->setType('class')
      ->setLibrary('phutil')
      ->setAncestorClass(PhutilRemarkupBlockRule::class)
      ->setConcreteOnly(true)
      ->loadObjects();
    $phlab_blocks = (new PhutilSymbolLoader())
      ->setType('class')
      ->setLibrary($current_library)
      ->setAncestorClass(PhutilRemarkupBlockRule::class)
      ->setConcreteOnly(true)
      ->loadObjects();

    // Merge block rules from `phutil` and `phlab`.
    $blocks = array_merge($phutil_blocks, $phlab_blocks);

    // Order `$blocks` by priority.
    usort(
      $blocks,
      function (PhutilRemarkupBlockRule $a, PhutilRemarkupBlockRule $b): bool {
        return $a->getPriority() <=> $b->getPriority();
      });

    foreach ($blocks as $block) {
      // TODO: Why do we need this?
      if (!$block instanceof PhutilRemarkupCodeBlockRule) {
        $block->setMarkupRules($rules);
      }
    }

    $engine->setBlockRules($blocks);
    return $engine;
  }

}

<?php

/**
 * Insert a literal newline.
 *
 * See https://discourse.phabricator-community.org/t/request-for-a-newline-in-a-table-t5427/1618.
 */
final class PhabricatorNewLineRemarkupRule extends PhabricatorRemarkupCustomInlineRule {

  public function getPriority(): float {
    return 300.0;
  }

  public function apply($text): string {
    return preg_replace_callback(
      '@{newline}@m',
      [$this, 'markupNewLine'],
      $text);
  }

  private function markupNewLine(array $matches): string {
    $engine = $this->getEngine();

    if ($engine->isTextMode()) {
      return "\n";
    }

    return $engine->storeText(phutil_tag('br'));
  }

}

<?php

final class ManiphestBrowserDatasource extends ManiphestStaticMapDatasource {

  public function __construct() {
    // note: make sure any changes to keys are reflected in
    // PhabricatorBugCategorizationTokenQuery::getBuiltinTokens
    $this->setDataMapping([
      'browser_chrome' => [
        'display' => 'Chrome',
        'description' => 'Google Chrome',
      ],
      'browser_firefox' => [
        'display' => 'Firefox',
      ],
      'browser_ie' => [
        'display' => 'Internet Explorer',
        'description' => 'The browser we all love',
      ],
      'browser_safari' => [
        'display' => 'Safari',
      ],
      'browser_others' => [
        'display' => 'Others',
      ],
      ]);

    $this->setBrowseTitle(pht('Browsers'));
    $this->setPlaceholderText(
      pht('Input the browser(s) from where the bug was discovered'));
    $this->setIcon('fa-globe');
    $this->setColor('green');
  }

  public function getGenericResultDescription(): string {
    return 'Web Browser';
  }
}

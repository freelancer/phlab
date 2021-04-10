<?php

final class ManiphestPlatformDatasource extends ManiphestStaticMapDatasource {

  public function __construct() {
    // note: make sure any changes to keys are reflected in
    // PhabricatorBugCategorizationTokenQuery::getBuiltinTokens
    $this->setDataMapping([
      'platform_linux' => [
        'display' => 'Linux',
      ],
      'platform_mac' => [
        'display' => 'Mac',
      ],
      'platform_windows' => [
        'display' => 'Windows',
      ],
      'platform_android' => [
        'display' => 'Android',
      ],
      'platform_ios' => [
        'display' => 'iOS',
      ],
      'platform_desktop_app' => [
        'display' => 'Desktop App',
      ],
      'platform_others' => [
        'display' => 'Others',
      ],
      ]);

    $this->setBrowseTitle(pht('Platforms'));
    $this->setPlaceholderText(
      pht('Enter the platform(s) on which this bug was found'));
    $this->setIcon('fa-desktop');
    $this->setColor('yellow');
  }

  public function getGenericResultDescription(): string {
    return 'Platform';
  }
}

<?php

final class ManiphestTypeOfBugsDatasource extends ManiphestStaticMapDatasource {

  public function __construct() {
    // note: make sure any changes to keys are reflected in
    // PhabricatorBugCategorizationTokenQuery::getBuiltinTokens
    $this->setDataMapping([
      'type_of_bug_bad_ux' => ['display' => 'Bad UX'],
      'type_of_bug_browser_compatibility' => [
        'display' => 'Browser Compatibility',
      ],
      'type_of_bug_error_handling' => ['display' => 'Error Handling'],
      'type_of_bug_functionality' => ['display' => 'Functionality'],
      'type_of_bug_localization' => ['display' => 'Localization'],
      'type_of_bug_performance' => ['display' => 'Performance'],
      'type_of_bug_ui' => ['display' => 'UI'],
      ]);

    $this->setBrowseTitle(pht('Type of Bug'));
    $this->setPlaceholderText(
      pht('Enter all applicable classifications(s) for this bug'));
    $this->setIcon('fa-bug');
    $this->setColor('violet');
  }

  public function getGenericResultDescription(): string {
    return 'Type of Bug';
  }
}

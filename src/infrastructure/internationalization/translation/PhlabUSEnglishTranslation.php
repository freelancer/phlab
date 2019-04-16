<?php

final class PhlabUSEnglishTranslation extends PhutilTranslation {

  public function getLocaleCode(): string {
    return 'en_US';
  }

  protected function getTranslations(): array {
    return [
      // PhabricatorOwnedByProjectEdgeType
      '%s added %s owner project(s): %s.' => [
        [
          '%s added owner project: %3$s.',
          '%s added %s owner projects: %s.',
        ],
      ],
      '%s added %s owner project(s) for %s: %s.' => [
        [
          '%s added owner project for %3$s: %4$s.',
          '%s added %s owner projects for %s: %s.',
        ],
      ],
      '%s edited owner project(s), added %s: %s; removed %s: %s.' =>
        '%s edited owner projects, added %3$s; removed: %5$s.',
      '%s edited owner project(s) for %s, added %s: %s; removed %s: %s.' =>
        '%s edited owner projects for %s, added %4$s; removed: %6$s.',
      '%s removed %s owner project(s): %s.' => [
        [
          '%s removed owner project: %3$s.',
          '%s removed %s owner projects: %s.',
        ],
      ],
      '%s removed %s owner project(s) for %s: %s.' => [
        [
          '%s removed owner project for %3$s: %4$s.',
          '%s removed %s owner projects for %s: %s.',
        ],
      ],

      // PhabricatorOwnsProjectEdgeType
      '%s added %s owned project(s): %s.' => [
        [
          '%s added owned project: %3$s.',
          '%s added %s owned projects: %s.',
        ],
      ],
      '%s added %s owned project(s) for %s: %s.' => [
        [
          '%s added owned project for %3$s: %4$s.',
          '%s added %s owned projects for %s: %s.',
        ],
      ],
      '%s edited owned project(s), added %s: %s; removed %s: %s.' =>
        '%s edited owned projects, added %3$s; removed %5$s.',
      '%s edited owned project(s) for %s, added %s: %s; removed %s: %s.' =>
        '%s edited owned projects for %s, added %4$s; removed: %6$s.',
      '%s removed %s owned project(s): %s.' => [
        [
          '%s removed owned project: %3$s.',
          '%s removed %s owned projects: %s.',
        ],
      ],
      '%s removed %s owned project(s) for %s: %s.' => [
        [
          '%s removed owned project for %3$s: %4$s.',
          '%s removed %s owned projects for %s: %s.',
        ],
      ],
    ];
  }

}

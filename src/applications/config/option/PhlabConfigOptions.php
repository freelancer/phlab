<?php

final class PhlabConfigOptions extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Phlab');
  }

  public function getDescription() {
    return pht("Configure Freelancer's Phabricator extensions.");
  }

  public function getFontIcon() {
    return 'fa-flask';
  }

  public function getOptions() {
    return array(
      $this->newOption('phlab.composer-path', 'string', '/usr/src/composer')
        ->setLocked(true)
        ->setSummary(pht('Composer root directory.'))
        ->setDescription(
          pht(
            'The root directory for packages installed with %s.',
            '[[https://getcomposer.org/ | Composer]].')),
    );
  }

}

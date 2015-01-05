<?php

final class HipChatConfigOptions extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('HipChat');
  }

  public function getDescription() {
    return pht('Configure HipChat.');
  }

  public function getOptions() {
    return array(
      $this->newOption('hipchat.token', 'string', null)
        ->setMasked(true)
        ->setSummary(pht('HipChat API token.'))
        ->setDescription(
          pht('The HipChat API token to use for publishing to HipChat.')),
    );
  }

}

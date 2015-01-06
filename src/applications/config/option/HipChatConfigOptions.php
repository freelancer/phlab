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
      $this->newOption('hipchat.author', 'string', 'Phabricator')
        ->setSummary(pht('HipChat Notifications Author.'))
        ->setDescription(
          pht('The name to use when publishing notifications to HipChat.')),
      $this->newOption('hipchat.color', 'enum', 'green')
        ->setSummary(pht('HipChat Notification Color.'))
        ->setDescription(
          pht('The color to use when publishing notifications to HipChat.'))
        ->setEnumOptions(array('red', 'yellow', 'green', 'purple')),
      $this->newOption('hipchat.token', 'string', null)
        ->setMasked(true)
        ->setSummary(pht('HipChat API token.'))
        ->setDescription(
          pht('The HipChat API token to use for publishing to HipChat.')),
    );
  }

}

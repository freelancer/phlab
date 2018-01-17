<?php

final class RocketChatConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Integration with Rocket.Chat');
  }

  public function getDescription() {
    return pht('Configure Rocket.Chat integration.');
  }

  public function getGroup() {
    return 'core';
  }

  public function getFontIcon() {
    return 'fa-comments';
  }

  public function getOptions() {
    return [
      $this->newOption('rocketchat.author', 'string', 'Phabricator')
        ->setSummary(pht('Rocket.Chat notifications author.'))
        ->setDescription(
          pht('The name to use when publishing notifications to Rocket.Chat.')),
      $this->newOption('rocketchat.server', 'string', null)
        ->setSummary(pht('Rocket.Chat server URL.'))
        ->setDescription(pht('Base URL of Rocket.Chat Server.')),
      $this->newOption('rocketchat.user-id', 'string', null)
        ->setSummary(pht('Rocket.Chat User ID.'))
        ->setDescription(
          pht('The Rocket.Chat User Id to use for publishing to Rocket.Chat.')),
      $this->newOption('rocketchat.token', 'string', null)
        ->setHidden(true)
        ->setSummary(pht('Rocket.Chat API token.'))
        ->setDescription(
          pht('The Rocket.Chat API token to use for publishing to Rocket.Chat.')),
    ];
  }

  public function getKey() {
    return 'rocketchat';
  }

}

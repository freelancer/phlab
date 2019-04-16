<?php

final class RocketChatConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName(): string {
    return pht('Integration with Rocket.Chat');
  }

  public function getDescription(): string {
    return pht('Configure Rocket.Chat integration.');
  }

  public function getGroup(): string {
    return 'core';
  }

  public function getOptions(): array {
    return [
      $this->newOption('rocketchat.author', 'string', 'Phabricator')
        ->setSummary(pht('Rocket.Chat notifications author.'))
        ->setDescription(
          pht('The name to use when publishing notifications to Rocket.Chat.')),
      $this->newOption('rocketchat.server', 'string', null)
        ->addExample('https://open.rocket.chat', pht('Demo Server'))
        ->setSummary(pht('Rocket.Chat server URL.'))
        ->setDescription(pht('Base URL of Rocket.Chat Server.')),
      $this->newOption('rocketchat.user-id', 'string', null)
        ->setSummary(pht('Rocket.Chat user ID.'))
        ->setDescription(
          pht('The Rocket.Chat User ID to use for publishing to Rocket.Chat.')),
      $this->newOption('rocketchat.token', 'string', null)
        ->setHidden(true)
        ->setSummary(pht('Rocket.Chat API token.'))
        ->setDescription(
          pht('The Rocket.Chat API token to use for publishing to Rocket.Chat.')),
    ];
  }

  public function getIcon(): string {
    return 'fa-comments';
  }

  protected function didValidateOption(PhabricatorConfigOption $option, $value): void {
    switch ($option->getKey()) {
      case 'rocketchat.server':
        if (!preg_match('(^https?://)', $value)) {
          throw new PhabricatorConfigValidationException(
            pht(
              "Configuration option '%s' is not a valid URI.",
              $value));
        }

        break;
    }
  }

  public function getKey(): string {
    return 'rocketchat';
  }

}

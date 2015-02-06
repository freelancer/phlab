<?php

final class HipChatConfigOptions extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Integration with HipChat');
  }

  public function getDescription() {
    return pht('Configure HipChat integration.');
  }

  public function getFontIcon() {
    return 'fa-comments';
  }

  public function getOptions() {
    static $colors = array(
      'gray',
      'green',
      'purple',
      'random',
      'red',
      'yellow',
    );

    return array(
      $this->newOption('hipchat.author', 'string', 'Phabricator')
        ->setSummary(pht('HipChat Notifications Author.'))
        ->setDescription(
          pht('The name to use when publishing notifications to HipChat.')),
      $this->newOption('hipchat.color', 'enum', 'green')
        ->setSummary(pht('HipChat Notification Color.'))
        ->setDescription(
          pht('The color to use when publishing notifications to HipChat.'))
        ->setEnumOptions($colors),
      $this->newOption('hipchat.token', 'string', null)
        ->setMasked(true)
        ->setSummary(pht('HipChat API token.'))
        ->setDescription(
          pht('The HipChat API token to use for publishing to HipChat.')),
    );
  }

}

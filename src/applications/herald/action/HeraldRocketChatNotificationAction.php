<?php

/**
 * A Herald action for sending notifications to RocketChat rooms.
 */
final class HeraldRocketChatNotificationAction extends HeraldAction {

  const ACTIONCONST = 'rocketchat.notification';

  const DO_FAILED = 'do.failed';
  const DO_NOTIFY = 'do.notify';

  public function getHeraldActionName() {
    return pht('Notify a Rocket.Chat room');
  }

  public function supportsObject($object) {
    if (
      $object instanceof DifferentialRevision ||
      $object instanceof ManiphestTask ||
      $object instanceof PonderQuestion
    ) {
      return true;
    } else {
      return false;
    }
  }

  public function supportsRuleType($rule_type) {
    return $rule_type == HeraldRuleTypeConfig::RULE_TYPE_GLOBAL;
  }

  public function applyEffect($object, HeraldEffect $effect) {
    $handle = id(new PhabricatorHandleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs([$this->getAdapter()->getPHID()])
      ->executeOne();

    $action = $this->getAdapter()->getIsNewObject()
      ? pht('New')
      : pht('Updated');

    try {
      $text = sprintf('*%s*: <%s|%s>',
        $action,
        $this->getURI($handle),
        $object->getMonogram().': '.$object->getTitle());

      $this->getClient()->messageRoom(
        $effect->getTarget(),
        PhabricatorEnv::getEnvConfig('rocketchat.author'),
        $text);
      $this->logEffect(self::DO_NOTIFY, $effect->getTarget());
    } catch (Exception $ex) {
      $this->logEffect(self::DO_FAILED, $ex->getMessage());
    }
  }

  public function renderActionDescription($value) {
    return pht('Notify the "%s" Rocket.Chat channel.', $value);
  }

  protected function renderActionEffectDescription($type, $data) {
    switch ($type) {
      case self::DO_FAILED:
        return pht('Failed with: "%s"', $data);

      case self::DO_NOTIFY:
        return pht('Notified the "%s" room.', $data);
    }
  }

  public function getActionGroupKey() {
    return HeraldNotifyActionGroup::ACTIONGROUPKEY;
  }

  public function getHeraldActionStandardType() {
    return self::STANDARD_TEXT;
  }

  protected function getActionEffectMap() {
    return array(
      self::DO_FAILED => [
        'icon' => 'fa-times',
        'color' => 'red',
        'name' => pht('Notification Failed'),
      ],
      self::DO_NOTIFY => [
        'icon' => 'fa-envelope',
        'color' => 'green',
        'name' => pht('Notified'),
      ],
    );
  }

  /**
   * Create a new RocketChat API object.
   */
  protected function getClient() {
    $server  = PhabricatorEnv::getEnvConfig('rocketchat.server');
    $user_id = PhabricatorEnv::getEnvConfig('rocketchat.user-id');
    $token   = PhabricatorEnv::getEnvConfig('rocketchat.token');

    if (!strlen($user_id)) {
      throw new Exception(pht('No Rocket.Chat User ID specified!'));
    }

    if (!strlen($token)) {
      throw new Exception(pht('No Rocket.Chat API token specified!'));
    }

    if (!strlen($server)) {
      throw new Exception(pht('No Rocket.Chat server is specified!'));
    }

    return new RocketChatClient($user_id, $token, $server);
  }

  /**
   * Create the notification message.
   *
   * @param  string
   * @param  string
   * @param  PhabricatorObjectHandle
   * @return string
   */
  private function getURI(PhabricatorObjectHandle $handle) {
    return (string)PhabricatorEnv::getURI($handle->getURI());
  }

}

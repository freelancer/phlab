<?php

/**
 * A Herald action for sending notifications to HipChat rooms.
 */
final class HeraldHipChatNotificationAction extends HeraldAction {

  const ACTIONCONST = 'hipchat.notification';

  const DO_FAILED = 'do.failed';
  const DO_NOTIFY = 'do.notify';

  public function getHeraldActionName() {
    return pht('Notify a HipChat room');
  }

  public function supportsObject($object) {
    if ($object instanceof DifferentialRevision) {
      return true;
    } else if ($object instanceof ManiphestTask) {
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
      $this->getClient()->messageRoom(
        $effect->getTarget(),
        PhabricatorEnv::getEnvConfig('hipchat.author'),
        (string)$this->getMessage(
          $action,
          pht('%s: %s', $object->getMonogram(), $object->getTitle()),
          $handle),
        false,
        PhabricatorEnv::getEnvConfig('hipchat.color'));
      $this->logEffect(self::DO_NOTIFY, $effect->getTarget());
    } catch (Exception $ex) {
      $this->logEffect(self::DO_FAILED, $ex->getMessage());
    }
  }

  public function renderActionDescription($value) {
    return pht('Notify the "%s" room.', $value);
  }

  protected function renderActionEffectDescription($type, $data) {
    switch ($type) {
      case self::DO_FAILED:
        return pht('Failed to notify the "%s" room.', $data);

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
   * Create a new HipChat API object.
   */
  protected function getClient() {
    $server = PhabricatorEnv::getEnvConfig('hipchat.server');
    $token  = PhabricatorEnv::getEnvConfig('hipchat.token');

    if (!$token) {
      throw new Exception(pht('No HipChat API token specified!'));
    }

    return new HipChatClient($token, $server);
  }

  /**
   * Create the notification message.
   *
   * @param  string
   * @param  string
   * @param  PhabricatorObjectHandle
   * @return string
   */
  private function getMessage(
    $action,
    $title,
    PhabricatorObjectHandle $handle) {

    $header = phutil_tag(
      'div',
      [],
      [
        phutil_tag('b', [], $action.': '),
        phutil_tag(
          'a',
          ['href' => PhabricatorEnv::getURI($handle->getURI())],
          $title),
      ]);

    return (string)phutil_tag('div', [], $header);
  }

}

<?php

/**
 * A Herald action for sending notifications to HipChat rooms.
 *
 * This class uses [[https://github.com/hipchat/hipchat-php/ |
 * hipchat/hipchat-php]] to communicate with the
 * [[https://www.hipchat.com/docs/api | HipChat API]].
 */
final class HeraldHipChatNotificationCustomAction extends HeraldCustomAction {

  public function appliesToAdapter(HeraldAdapter $adapter) {
    return $adapter instanceof HeraldManiphestTaskAdapter;
  }

  public function appliesToRuleType($rule_type) {
    return $rule_type == HeraldRuleTypeConfig::RULE_TYPE_GLOBAL;
  }

  public function getActionKey() {
    return 'hipchat.notification';
  }

  public function getActionName() {
    return pht('Notify a HipChat room');
  }

  public function getActionType() {
    return HeraldAdapter::VALUE_TEXT;
  }

  public function applyEffect(
    HeraldAdapter $adapter,
    $object,
    HeraldEffect $effect) {

    $task = $adapter->getTask();
    $handle = id(new PhabricatorHandleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($task->getPHID()))
      ->executeOne();

    try {
      $client = $this->getClient();

      $client->message_room(
        $effect->getTarget(),
        PhabricatorEnv::getEnvConfig('hipchat.author'),
        (string)phutil_tag(
          'div',
          array(),
          array(
            phutil_tag('b', array(), 'A new ticket was created: '),
            phutil_tag(
              'a',
              array('href' => PhabricatorEnv::getURI($handle->getURI())),
              $task->getMonogram().': '.$task->getTitle()),
          )),
        false,
        PhabricatorEnv::getEnvConfig('hipchat.color'));

      return new HeraldApplyTranscript(
        $effect,
        true,
        pht('Notified HipChat room.'));
    } catch (Exception $ex) {
      return new HeraldApplyTranscript($effect, false, $ex->getMessage());
    }
  }

  /**
   * Create a new HipChat API object.
   *
   * @phutil-external-symbol class HipChat\HipChat
   */
  protected function getClient() {
    Composer::registerAutoloader();
    return new HipChat\HipChat($this->getApiToken());
  }

  /**
   * Retrieve the configured API token.
   *
   * @return string  HipChat API token.
   */
  private function getApiToken() {
    $token = PhabricatorEnv::getEnvConfig('hipchat.token');

    if (!$token) {
      throw new Exception('No HipChat API token specified!');
    }

    return $token;
  }

}

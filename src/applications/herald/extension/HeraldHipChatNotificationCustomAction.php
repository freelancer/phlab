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

    $rule = id(new HeraldRule())->load($effect->getRuleID());
    $user = id(new PhabricatorUser())->loadOneWhere(
      'phid = %s',
      $rule->getAuthorPHID());

    $task = $adapter->getTask();
    $handle = id(new PhabricatorHandleQuery())
      ->setViewer($user)
      ->withPHIDs(array($task->getPHID()))
      ->executeOne();

    try {
      $client = $this->getClient();
      $room = str_replace(' ', '_', $effect->getTarget());

      $client->message_room(
        $room,
        PhabricatorEnv::getEnvConfig('hipchat.author'),
        (string)phutil_tag(
          'span',
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

  private function getApiToken() {
    $token = PhabricatorEnv::getEnvConfig('hipchat.token');

    if (!$token) {
      throw new Exception('No HipChat API token specified!');
    }

    return $token;
  }

}

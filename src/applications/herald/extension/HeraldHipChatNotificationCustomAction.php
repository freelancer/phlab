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

    $handle = id(new PhabricatorHandleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($adapter->getPHID()))
      ->executeOne();

    $author = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array(
        $adapter->getHeraldField(HeraldAdapter::FIELD_AUTHOR),
      ))
      ->executeOne();

    $assignee = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array(
        $adapter->getHeraldField(HeraldAdapter::FIELD_ASSIGNEE),
      ))
      ->executeOne();

    try {
      $client = $this->getClient();

      $client->message_room(
        $effect->getTarget(),
        PhabricatorEnv::getEnvConfig('hipchat.author'),
        (string) $this->getMessage(
          pht('A new task was created'),
          sprintf(
            '%s: %s',
            $object->getMonogram(),
            $adapter->getHeraldField(HeraldAdapter::FIELD_TITLE)),
          $handle,
          array(
            pht('Assigned') => $assignee
              ? $assignee->getUsername()
              : phutil_tag('em', array(), pht('None')),
            pht('Priority') => ManiphestTaskPriority::getTaskPriorityName(
              $adapter->getHeraldField(HeraldAdapter::FIELD_TASK_PRIORITY)),
            pht('Author') => $author->getUsername(),
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

  /**
   * Create the notification message.
   *
   * @param  string
   * @param  string
   * @param  PhabricatorObjectHandle
   * @param  map<string, string>
   * @return string
   */
  private function getMessage(
    $action,
    $title,
    PhabricatorObjectHandle $handle,
    array $attributes) {

    $header = phutil_tag(
      'div',
      array(),
      array(
        phutil_tag('b', array(), $action.': '),
        phutil_tag(
          'a',
          array('href' => PhabricatorEnv::getURI($handle->getURI())),
          $title),
      ));

    $details = array();

    foreach ($attributes as $key => $value) {
      $details[] = phutil_tag(
        'li',
        array(),
        array(
          phutil_tag('b', array(), $key.': '),
          $value,
        ));
    }

    return (string) phutil_tag(
      'div',
      array(),
      array(
        $header,
        phutil_tag('br'),
        phutil_tag(
          'ul',
          array(),
          $details),
      ));
  }

}

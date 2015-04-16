<?php

/**
 * A Herald action for sending notifications to HipChat rooms.
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

    $action = $adapter->getHeraldField(HeraldAdapter::FIELD_IS_NEW_OBJECT)
      ? pht('A new task was created')
      : pht('A task was updated');

    try {
      $client = $this->getClient();

      $client->messageRoom(
        $effect->getTarget(),
        PhabricatorEnv::getEnvConfig('hipchat.author'),
        (string) $this->getMessage(
          $action,
          sprintf(
            '%s: %s',
            $object->getMonogram(),
            $adapter->getHeraldField(HeraldAdapter::FIELD_TITLE)),
          $handle),
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
   */
  protected function getClient() {
    $server = PhabricatorEnv::getEnvConfig('hipchat.server');
    $token  = PhabricatorEnv::getEnvConfig('hipchat.token');

    if (!$token) {
      throw new Exception('No HipChat API token specified!');
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
      array(),
      array(
        phutil_tag('b', array(), $action.': '),
        phutil_tag(
          'a',
          array('href' => PhabricatorEnv::getURI($handle->getURI())),
          $title),
      ));

    return (string) phutil_tag('div', array(), $header);
  }

}

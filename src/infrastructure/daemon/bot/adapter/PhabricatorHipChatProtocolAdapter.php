<?php

/**
 * [[https://www.hipchat.com/ | HipChat]] adapter for Phabricator's
 * [[https://secure.phabricator.com/book/phabdev/article/chatbot/ | Chatbot]].
 *
 * This class uses [[https://packagist.org/packages/abhinavsingh/jaxl |
 * `abhinavsingh/jaxl`]] to communicate with HipChat using [[http://xmpp.org/ |
 * XMPP]] (originally known as [[http://www.jabber.org/ | Jabber]]). See also
 * [[https://confluence.atlassian.com/pages/viewpage.action?pageId=751436251 |
 * Setting up XMPP/Jabber clients for HipChat]].
 *
 * @phutil-external-symbol class JAXL
 * @phutil-external-symbol class JAXLClock
 * @phutil-external-symbol class JAXLLoop
 * @phutil-external-symbol class XMPPJid
 * @phutil-external-symbol class XMPPStanza
 *
 * @task impl     Protocol Adapter Implementation
 * @task utility  Utilities
 * @task xmpp     XMPP Callbacks
 */
final class PhabricatorHipChatProtocolAdapter
  extends PhabricatorProtocolAdapter {

  private $client;
  private $messages;
  private $rooms;

  private $server;
  private $mucServer;
  private $port;
  private $user;
  private $password;
  private $nickname;

  /**
   * Destructor.
   *
   * Disconnect the XMPP client upon destruction. This is possibly not strictly
   * necessary, but it is technically correct.
   */
  public function __destruct() {
    if ($this->client) {
      $this->client->disconnect();
    }
  }


/* -(  Protocol Adapter Implementation  )------------------------------------ */


  /**
   * @task impl
   */
  public function connect() {
    // TODO: Is there a better place for this?
    Composer::registerAutoloader();

    // TODO: Maybe we should add some error checking here?
    $account_id = $this->getConfig('account_id');
    $user_id    = $this->getConfig('user_id');

    $this->server    = $this->getConfig('server', 'chat.hipchat.com');
    $this->mucServer = $this->getConfig('muc_server', 'conf.hipchat.com');
    $this->port      = $this->getConfig('port', 5222);
    $this->user      = sprintf('%d_%d', $account_id, $user_id);
    $this->password  = new PhutilOpaqueEnvelope($this->getConfig('password'));
    $this->nickname  = $this->getConfig('nickname');

    // We want to use human-readable names rather than Jabber IDs. For example,
    // we want the "channel" in the Chatlog application to be a human-readable
    // room name. We must, however, use Jabber IDs to connect to rooms over
    // XMPP. As such, we store a mapping from human-readable room name to
    // Jabber ID in `$this->rooms`.
    $this->rooms = array();
    foreach ($this->getConfig('rooms', array()) as $room) {
      $this->rooms[$room] = sprintf(
        '%d_%s@%s',
        $account_id,
        strtolower(str_replace(' ', '_', $room)),
        $this->mucServer);
    }

    $this->messages = array();

    $this->client = new JAXL(array(
      'jid' => $this->user.'@'.$this->server,
      'pass' => $this->password->openEnvelope(),
      'host' => $this->server,
      'port' => $this->port,
      'log_color' => false,
      'log_level' => $this->getConfig('log_level', JAXL_INFO),
      'sock_dir' => PhabricatorEnv::getEnvConfig('phd.pid-directory'),

      // If we don't disable strict mode, JAXL will install error and
      // exception handlers.
      'strict' => false,
    ));
    $this->client->require_xep(array(
      '0045', // MUC
      '0199', // XMPP Ping
    ));

    // Register XMPP event callbacks.
    $callbacks = array(
      'on_auth_failure' => array($this, 'onAuthFailure'),
      'on_auth_success' => array($this, 'onAuthSuccess'),
      'on_chat_message' => array($this, 'onChatMessage'),
      'on_connect' => array($this, 'onConnect'),
      'on_connect_error' => array($this, 'onConnectError'),
      'on_disconnect' => array($this, 'onDisconnect'),
      'on_error_message' => array($this, 'onErrorMessage'),
      'on_groupchat_message' => array($this, 'onGroupMessage'),
    );
    foreach ($callbacks as $event => $callback) {
      $this->client->add_cb($event, $callback);
    }

    $this->client->connect($this->client->get_socket_path());
    $this->client->start_stream();

    JAXLLoop::$clock = new JAXLClock();
  }

  /**
   * @param  int
   * @return list<PhabricatorBotMessage>
   *
   * @task impl
   */
  public function getNextMessages($poll_frequency) {
    // TODO: Currently we do not respect `$poll_frequency`.
    JAXLLoop::select();

    $messages = $this->messages;
    $this->messages = array();

    return $messages;
  }

  /**
   * @return string
   *
   * @task impl
   */
  public function getServiceName() {
    return $this->server;
  }

  /**
   * @return string
   *
   * @task impl
   */
  public function getServiceType() {
    return 'HipChat';
  }

  /**
   * @param  PhabricatorBotMessage
   * @return bool
   *
   * @task impl
   */
  public function writeMessage(PhabricatorBotMessage $message) {
    switch ($message->getCommand()) {
      case 'MESSAGE':
        $this->client->xeps['0045']->send_groupchat(
          idx($this->rooms, $message->getTarget()->getName()),
          $message->getBody());
        return true;

      default:
        return false;
    }
  }


/* -(  Utility  )------------------------------------------------------------ */


  /**
   * Convert a Jabber ID into a human-readable room name.
   *
   * @param  XMPPJid
   * @return string|null
   *
   * @task utility
   */
  protected function getRoomFromJid(XMPPJid $jid) {
    return idx(array_flip($this->rooms), $jid->node.'@'.$jid->domain);
  }

  /**
   * Log a message.
   *
   * Logs a message to the daemon logs.
   *
   * @param  string
   * @return this
   *
   * @task utility
   */
  protected function log($message) {
    phlog($message);
    return $this;
  }

  /**
   * Join a room.
   *
   * Joins a specified room using [[http://xmpp.org/extensions/xep-0045.html |
   * XEP-0045]].
   *
   * @param  string
   * @return this
   *
   * @task utility
   *
   * @todo Add error handling.
   * @todo Can we typehint `$room` as `XMPPJid`?
   */
  protected function joinRoom($room) {
    $this->client->xeps['0045']->join_room(
        $room.'/'.$this->nickname,
        array('no_history' => true));
    return $this;
  }


/* -(  XMPP Callbacks  )----------------------------------------------------- */


  /**
   * Callback for authentication failures.
   *
   * This callback is called whenever there is an authentication failure.
   *
   * ```lang=xml, name=Sample response
   * <failure xmlns='urn:ietf:params:xml:ns:xmpp-sasl'><not-authorized/></failure>
   * ```
   *
   * @param  string
   * @return void
   *
   * @task xmpp
   */
  public function onAuthFailure($reason) {
    $this->log(pht('Authentication Failure: %s', $reason));
  }

  /**
   * Callback for successful authentication.
   *
   * This callback is called after successful authentication. This callback is
   * primarily used to join rooms, which can only be done after authentication.
   *
   * @return void
   *
   * @task xmpp
   */
  public function onAuthSuccess() {
    $this->log(pht('Authentication Success'));

    // TODO: Maybe set a more useful status.
    $this->client->set_status('');

    // TODO: What happens if joining a room fails?
    foreach ($this->rooms as $room) {
      $this->joinRoom($room);
    }
  }

  /**
   * Callback for connection errors.
   *
   * This callback is called whenever there is a connection error.
   *
   * @param  int     Error number.
   * @param  string  Error string.
   * @return void
   *
   * @task xmpp
   */
  public function onConnectError($errno, $errstr) {
    $this->log(pht('Connection Error #%d: %s', $errno, $errstr));
  }

  /**
   * Connection callback.
   *
   * This callback is invoked on successful connection.
   *
   * @return void
   *
   * @task xmpp
   */
  public function onConnect() {
    $this->log(pht('Connected'));
  }

  /**
   * Disconnection callback.
   *
   * This callback is invoked after disconnection.
   *
   * @return void
   *
   * @task xmpp
   */
  public function onDisconnect() {
    $this->log(pht('Disconnected'));
  }

  /**
   * Callback for chat messages.
   *
   * Whenever a private chat message is received, the original message is
   * simply echoed back to the sender.
   *
   * TODO: This should probably do something more useful eventually.
   *
   * ```lang=xml, name=Sample response
   * <message xmlns="jabber:client" to="12345_1234567@chat.hipchat.com/linux||proxy|proxy-b101.hipchat.com|5272" from="12345_2345678@chat.hipchat.com/jaxl#f78e1cec4009ea4c0f89056b10db1514||proxy|pubproxy-b100.hipchat.com|5262" mid="c486c7c0-9160-4feb-8845-ac14f7234c9c" ts="1451874235.800156" type="chat"><body>Hello world</body></message>
   * ```
   *
   * @param  XMPPStanza
   * @return void
   *
   * @task xmpp
   */
  public function onChatMessage(XMPPStanza $stanza) {
    $message = clone $stanza;

    $message->to   = $stanza->from;
    $message->from = $this->client->full_jid->to_string();

    $this->client->send($message);
  }

  /**
   * Callback for error messages.
   *
   * ```lang=xml, name=Sample response
   * <message to='12345_1234567@chat.hipchat.com/jaxl#c92e7f65a9fa71f31928744c23c2f196||proxy|pubproxy-c100.hipchat.com|5252' from='derp' type='error'><composing xmlns='http://jabber.org/protocol/chatstates'/><error code='400' type='modify'><bad-request xmlns='urn:ietf:params:xml:ns:xmpp-stanzas'/></error></message>
   * ```
   *
   * @param  XMPPStanza
   * @return void
   *
   * @task xmpp
   */
  public function onErrorMessage(XMPPStanza $stanza) {
    $error = $stanza->exists('error');

    $this->log(
      pht(
        'Error #%d (%s)',
        $error->attrs['code'],
        $error->attrs['type']));
  }

  /**
   * Callback for group messages.
   *
   * Whenever a group message is received, it is buffered to the internal
   * `$this->messages` array and later processed in @{method:getNextMessages}.
   *
   * ```lang=xml, name=Sample response
   * <message to='12345_1234567@chat.hipchat.com/jaxl#f78e1cec4009ea4c0f89056b10db1514||proxy|pubproxy-b100.hipchat.com|5262' from='12345_some_room@conf.hipchat.com/Abraham Lincoln' mid='5c63166e-7587-4fe1-a59b-1efc9401d2b8' ts='1451876066.562264' type='groupchat'><body>Hello World</body></message>
   * ```
   *
   * @param  XMPPStanza
   * @return void
   *
   * @task xmpp
   */
  public function onGroupMessage(XMPPStanza $stanza) {
    // For some unknown reason, a number of messages are received that have
    // a `null` body. These messages are just junk and can be safely discarded.
    if (!$stanza->exists('body')) {
      return;
    }

    $sender = new XMPPJid($stanza->from);
    $target = $this->getRoomFromJid($sender);

    $this->messages[] = id(new PhabricatorBotMessage())
      ->setCommand('MESSAGE')
      ->setSender(id(new PhabricatorBotUser())->setName($sender->resource))
      ->setTarget(id(new PhabricatorBotChannel())->setName($target))
      ->setBody(htmlspecialchars_decode($stanza->body));
  }

}

<?php

/**
 * Library for interacting with the HipChat REST API.
 *
 * Based on [[https://github.com/hipchat/hipchat-php/ | hipchat/hipchat-php]].
 *
 * @see http://api.hipchat.com/docs/api
 */
final class HipChatClient extends Phobject {

  const DEFAULT_TARGET = 'https://api.hipchat.com/v1/';

  // Colors.
  const COLOR_YELLOW = 'yellow';
  const COLOR_RED = 'red';
  const COLOR_GRAY = 'gray';
  const COLOR_GREEN = 'green';
  const COLOR_PURPLE = 'purple';
  const COLOR_RANDOM = 'random';

  // Formats.
  const FORMAT_HTML = 'html';
  const FORMAT_TEXT = 'text';

  private $apiTarget;
  private $authToken;

  /**
   * Creates a new API interaction object.
   *
   * @param string  API token.
   * @param string  API protocol and host.
   */
  public function __construct($auth_token, $api_target = self::DEFAULT_TARGET) {
    $this->authToken = $auth_token;
    $this->apiTarget = $api_target;
  }


/* -(  Rooms  )-------------------------------------------------------------- */

  /**
   * Get information about a room.
   *
   * @param  wild
   * @return wild
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/show
   */
  public function getRoom($room_id) {
    $response = $this->makeRequest('rooms/show', ['room_id' => $room_id]);
    return idx($response, 'room');
  }

  /**
   * Determine if the given room name or room ID already exists.
   *
   * @param  string
   * @return boolean
   */
  public function roomExists($room_id) {
    try {
      $this->getRoom($room_id);
    } catch (HTTPFutureHTTPResponseStatus $ex) {
      if ($ex->getStatusCode() === 404) {
        return false;
      } else {
        throw $ex;
      }
    }
    return true;
  }

  /**
   * Get list of rooms
   *
   * @return list<map<string, wild>>
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/list
   */
  public function getRooms() {
    $response = $this->makeRequest('rooms/list');
    return idx($response, 'rooms');
  }

  /**
   * Send a message to a room.
   *
   * @param  wild
   * @param  string
   * @param  string
   * @param  bool
   * @param  const
   * @param  const
   * @return bool
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/message
   */
  public function messageRoom(
    $room_id,
    $from,
    $message,
    $notify = false,
    $color = self::COLOR_YELLOW,
    $message_format = self::FORMAT_HTML) {

    $args = [
      'room_id' => $room_id,
      'from' => $from,
      'message' => $message,
      'notify' => (int)$notify,
      'color' => $color,
      'message_format' => $message_format,
    ];
    $response = $this->makeRequest('rooms/message', $args, 'POST');
    return idx($response, 'status') == 'sent';
  }

  /**
   * Get chat history for a room.
   *
   * @param  int
   * @param  string
   * @return list<map<string, wild>>
   *
   * @see https://www.hipchat.com/docs/api/method/rooms/history
   */
   public function getRoomsHistory($room_id, $date = 'recent') {
     $response = $this->makeRequest('rooms/history', [
      'room_id' => $room_id,
      'date' => $date,
     ]);
     return idx($response, 'messages');
   }

  /**
   * Set a room's topic.
   *
   * @param  string
   * @param  string
   * @param  string
   * @return bool
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/topic
   */
   public function setRoomTopic($room_id, $topic, $from = null) {
     $args = [
       'room_id' => $room_id,
       'topic' => $topic,
     ];

     if ($from) {
       $args['from'] = $from;
     }

     $response = $this->makeRequest('rooms/topic', $args, 'POST');
     return idx($response, 'status') == 'ok';
   }


/* -(  Users  )-------------------------------------------------------------- */

  /**
   * Get information about a user.
   *
   * @param  int
   * @return map<string, wild>
   *
   * @see http://api.hipchat.com/docs/api/method/users/show
   */
  public function getUser($user_id) {
    $response = $this->makeRequest('users/show', ['user_id' => $user_id]);
    return idx($response, 'user');
  }

  /**
   * Get list of users.
   *
   * @return list<map<string, wild>>
   *
   * @see http://api.hipchat.com/docs/api/method/users/list
   */
  public function getUsers() {
    $response = $this->makeRequest('users/list');
    return idx($response, 'users');
  }


/* -(  Utility  )------------------------------------------------------------ */

  /**
   * Make an API request.
   *
   * @param  string
   * @param  map<string, wild>
   * @param  string
   * @return wild
   */
  private function makeRequest($api_method, $args = [], $http_method = 'GET') {
    $args['auth_token'] = $this->authToken;
    $args['format'] = 'json';

    $uri = new PhutilURI($this->apiTarget.$api_method);
    $uri->setQueryParams($args);

    $future = id(new HTTPSFuture($uri))
      ->setMethod($http_method);

    list($data, $headers) = $future->resolvex();
    return phutil_json_decode($data, true);
  }

}

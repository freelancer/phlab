<?php

/**
 * Library for interacting with the RocketChat REST API.
 *
 * @see https://rocket.chat/docs/developer-guides/rest-api/
 *
 * @todo Add unit tests.
 */
final class RocketChatClient extends Phobject {

  private $userId;
  private $apiTarget;
  private $authToken;

  /**
   * Creates a new API interaction object.
   *
   * @param string  API token.
   * @param string  API protocol and host.
   */
  public function __construct($user_id, $auth_token, $api_target) {
    $this->userId    = $user_id;
    $this->authToken = $auth_token;
    $this->apiTarget = $api_target;
  }

  /**
   * Send a message to a room.
   *
   * @param string  The channel.
   * @param string  The from name.
   * @param string  The message.
   * @param array   An array of attachment objects.
   */
  public function messageRoom(
    $channel,
    $from,
    $message,
    array $attachments = []) {

    $args = [
      'channel'     => $channel,
      'alias'       => $from,
      'text'        => $message,
      'attachments' => $attachments,
    ];
    $response = $this->makeRequest('chat.postMessage', $args);
    return idx($response, 'status') == 'sent';
  }

  /**
   * Make an API request.
   *
   * @param string               The API endpoint.
   * @param map<string, string>  The request arguments.
   */
  private function makeRequest($api_method, $args = []) {
    $request_headers = [
      'Content-Type' => 'application/json',
      'X-User-Id'    => $this->userId,
      'X-Auth-Token' => $this->authToken,
    ];

    $uri = new PhutilURI($this->apiTarget.'/api/v1/'.$api_method);

    $future = id(new HTTPSFuture($uri))
      ->setMethod('POST')
      ->setData(phutil_json_encode($args))
      ->setTimeout(2);

    foreach ($request_headers as $key => $value) {
      $future->addHeader($key, $value);
    }

    list($data, $headers) = $future->resolvex();
    return phutil_json_decode($data, true);
  }

}

<?php

/**
 * Client for interacting with the RocketChat REST API.
 *
 * @see https://rocket.chat/docs/developer-guides/rest-api/
 */
final class RocketChatClient extends Phobject {

  private $userId;
  private $apiTarget;
  private $authToken;

  /**
   * Creates a new API interaction object.
   *
   * @param string  User ID.
   * @param string  API token.
   * @param string  Target API host.
   */
  public function __construct(string $user_id, string $auth_token, string $api_target) {
    $this->userId    = $user_id;
    $this->authToken = $auth_token;
    $this->apiTarget = $api_target;
  }

  /**
   * Send a message to a room.
   *
   * @param  string  The channel.
   * @param  string  The from name.
   * @param  string  The message.
   * @return void
   */
  public function messageRoom(string $channel, string $from, string $message): void {
    $this->makeRequest('chat.postMessage', [
      'channel'     => $channel,
      'alias'       => $from,
      'text'        => $message,
    ]);
  }

  /**
   * Make an API request.
   *
   * @param  string               The API endpoint.
   * @param  map<string, string>  The request arguments.
   * @return map<string, wild>    API response.
   */
  private function makeRequest(string $api_method, array $args = []): array {
    $request_headers = [
      'Content-Type' => 'application/json',
      'X-User-Id'    => $this->userId,
      'X-Auth-Token' => $this->authToken,
    ];

    $uri = new PhutilURI($this->apiTarget.'/api/v1/'.$api_method);

    $future = (new HTTPSFuture($uri))
      ->setMethod('POST')
      ->setData(phutil_json_encode($args))
      ->setTimeout(2);

    foreach ($request_headers as $key => $value) {
      $future->addHeader($key, $value);
    }

    [$data, $headers] = $future->resolvex();
    return phutil_json_decode($data);
  }

}

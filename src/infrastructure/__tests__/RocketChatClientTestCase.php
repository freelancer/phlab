<?php

use InterNations\Component\HttpMock\Matcher\MatcherFactory;
use InterNations\Component\HttpMock\Matcher\ExtractorFactory;
use InterNations\Component\HttpMock\MockBuilder;
use InterNations\Component\HttpMock\RequestCollectionFacade;
use InterNations\Component\HttpMock\Server;

/**
 * @phutil-external-symbol class ExtractorFactory
 * @phutil-external-symbol class MatcherFactory
 * @phutil-external-symbol class MockBuilder
 * @phutil-external-symbol class RequestCollectionFacade
 * @phutil-external-symbol class Server
 */
final class RocketChatClientTestCase extends PhutilTestCase {

  const SERVER_HOST = 'localhost';
  const SERVER_PORT = 28080;

  private $mock;
  private $requests;
  private $server;

  protected function willRunTests(): void {
    $this->server = new Server(self::SERVER_PORT, self::SERVER_HOST);
    $this->server->start();
  }

  protected function didRunTests(): void {
    $this->server->stop();
  }

  protected function willRunOneTest($name): void {
    $this->mock = new MockBuilder(
      new MatcherFactory(),
      new ExtractorFactory());

    $this->requests = new RequestCollectionFacade(
      $this->server->getClient());
  }

  public function testMessageRoom(): void {
    $user_id    = 'user';
    $auth_token = 'auth';

    $client = new RocketChatClient(
      $user_id,
      $auth_token,
      $this->server->getBaseUrl());

    $this->mock
      ->when()
        ->methodIs('POST')
        ->pathIs('/api/v1/chat.postMessage')
      ->then()
        ->body(phutil_json_encode(['success' => true]))
      ->end();
    $this->server->setUp($this->mock->flushExpectations());

    $room    = 'room';
    $from    = 'from';
    $message = 'Hello World';

    $client->messageRoom($room, $from, $message);

    $this->assertEqual(1, count($this->requests));
    $request = $this->requests->latest();

    $this->assertEqual($user_id, (string)$request->getHeader('X-User-Id'));
    $this->assertEqual($auth_token, (string)$request->getHeader('X-Auth-Token'));
    $this->assertEqual('POST', $request->getMethod());
    $this->assertEqual('/api/v1/chat.postMessage', $request->getPath());
    $this->assertEqual(
      ['channel' => $room, 'alias' => $from, 'text' => $message],
      phutil_json_decode($request->getBody()));
  }

}

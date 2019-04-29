<?php

use Aws\MockHandler;
use Aws\Result;
use Aws\Ses\SesClient;

/**
 * @phutil-external-symbol class MockHandler
 * @phutil-external-symbol class Result
 */
final class PhlabSESMailAdapterTestCase extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testGetAllAdapters(): void {
    PhabricatorMailAdapter::getAllAdapters();
    $this->assertTrue(true);
  }

  public function testGetSupportedMessageTypes(): void {
    $adapter = new PhlabSESMailAdapter();

    $this->assertTrue($adapter->supportsMessageType(PhabricatorMailEmailMessage::MESSAGETYPE));
    $this->assertFalse($adapter->supportsMessageType(PhabricatorMailSMSMessage::MESSAGETYPE));
  }

  public function testSendMessage(): void {
    $user = $this->generateNewTestUser();

    $mock = new MockHandler();
    $mock->append(new Result(['MessageId' => 'XXX']));

    $mailer = new PhlabSESMailAdapter();
    $mailer->setOptions([
      'access-key' => 'XXX',
      'handler'    => $mock,
      'region'     => 'us-east-1',
      'secret-key' => 'XXX',
    ]);

    $mail = (new PhabricatorMetaMTAMail())
      ->addTos([$user->getPHID()])
      ->setBody(pht('Plaintext body.'))
      ->setHTMLBody(pht('HTML body.'));

    $mail->sendWithMailers([$mailer]);
    $this->assertEqual(
      PhabricatorMailOutboundStatus::STATUS_SENT,
      $mail->getStatus());
  }

}

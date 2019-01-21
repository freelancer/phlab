<?php

use Aws\Ses\SesClient;

/**
 * This mail adapter is similar to @{class:PhabricatorMailAmazonSESAdapter},
 * but supports the use of IAM credentials by using
 * [[http://aws.amazon.com/sdk-for-php/ | `aws-sdk-php`]].
 *
 * @todo This class will be obsolete after https://secure.phabricator.com/T5155.
 */
final class PhlabSESMailAdapter extends PhabricatorMailAdapter {

  const ADAPTERTYPE = 'aws-ses';

  public function getSupportedMessageTypes(): array {
    return [
      PhabricatorMailEmailMessage::MESSAGETYPE,
    ];
  }

  /**
   * @phutil-external-symbol class PHPMailerLite
   * @phutil-external-symbol class SesClient
   */
  public function sendMessage(PhabricatorMailExternalMessage $message): void {
    // TODO: We should possibly just add PHPMailer to the autoloader.
    require_once phutil_get_library_root('phabricator').'/../externals/phpmailer/class.phpmailer-lite.php';

    $mailer = PHPMailerLite::newFromMessage($message);

    $mailer->Mailer = 'amazon-ses';
    $mailer->customMailer = $this;

    $mailer->Send();
  }

  public function supportsMessageIDHeader(): bool {
    // Amazon SES will ignore any `Message-ID` we provide.
    return false;
  }

  protected function validateOptions(array $options): void {
    PhutilTypeSpec::checkMap(
      $options,
      [
        'region' => 'string',
      ]);
  }

  public function newDefaultOptions(): array {
    return [
      'region' => null,
    ];
  }

  public function executeSend($body): void {
    $ses = new SesClient([
      'region'  => $this->getOption('region'),
      'version' => 'latest',
    ]);

    $ses->sendRawEmail([
      'RawMessage' => [
        'Data' => $body,
      ],
    ]);
  }

}

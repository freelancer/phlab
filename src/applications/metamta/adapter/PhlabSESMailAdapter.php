<?php

use Aws\Ses\SesClient;

/**
 * This mail adapter is similar to @{class:PhabricatorMailAmazonSESAdapter},
 * but supports the use of IAM credentials by using
 * [[http://aws.amazon.com/sdk-for-php/ | `aws-sdk-php`]].
 *
 * @todo This class will be obsolete after https://secure.phabricator.com/T5155.
 *
 * @phutil-external-symbol class SesClient
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
   */
  public function sendMessage(PhabricatorMailExternalMessage $message): void {
    // TODO: We should possibly just add PHPMailer to the autoloader.
    require_once phutil_get_library_root('phabricator').'/../externals/phpmailer/class.phpmailer-lite.php';

    $mailer = PHPMailerLite::newFromMessage($message);
    $mailer->Mailer = 'amazon-ses';
    $mailer->customMailer = $this;

    // TODO: Catch exceptions.
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
        'access-key' => 'string | null',
        'region'     => 'string',
        'secret-key' => 'string | null',

        // This is intended only to be used for unit tests.
        'handler'    => 'wild | null',
      ]);
  }

  public function newDefaultOptions(): array {
    return [
      'access-key' => null,
      'handler'    => null,
      'region'     => null,
      'secret-key' => null,
    ];
  }

  public function executeSend(string $body): array {
    $config = [
      'handler' => $this->getOption('handler'),
      'region'  => $this->getOption('region'),
      'version' => 'latest',
    ];

    if ($this->getOption('access-key') || $this->getOption('secret-key')) {
      $config['credentials'] = [
        'key'    => $this->getOption('access-key'),
        'secret' => $this->getOption('secret-key'),
      ];
    }

    $client = new SesClient($config);

    return $client
      ->sendRawEmail([
        'RawMessage' => [
          'Data' => $body,
        ],
      ])
      ->toArray();
  }

}

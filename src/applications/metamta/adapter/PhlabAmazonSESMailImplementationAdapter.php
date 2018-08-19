<?php

use Aws\Ses\SesClient;

/**
 * This mail adapter is similar to
 * @{class:PhabricatorMailImplementationAmazonSESAdapter}, but supports the use
 * of [[http://docs.aws.amazon.com/STS/latest/UsingSTS/Welcome.html |
 * IAM credentials]].
 *
 * This file engine uses [[http://aws.amazon.com/sdk-for-php/ | aws-sdk-php]]
 * to interact with [[http://aws.amazon.com/ | Amazon Web Services]].
 *
 * @phutil-external-symbol class SesClient
 *
 * @todo This class will be obsolete after https://secure.phabricator.com/T5155.
 */
final class PhlabAmazonSESMailImplementationAdapter
  extends PhabricatorMailImplementationPHPMailerLiteAdapter {

  const ADAPTERTYPE = 'aws-ses';

  public function prepareForSend(): void {
    parent::prepareForSend();

    $this->mailer->Mailer = 'amazon-ses';
    $this->mailer->customMailer = $this;
  }

  public function supportsMessageIDHeader(): bool {
    // Amazon SES will ignore any Message-ID we provide.
    return false;
  }

  protected function validateOptions(array $options): void {
    PhutilTypeSpec::checkMap(
      $options,
      [
        'encoding' => 'string',
        'endpoint' => 'string',
      ]);
  }

  public function newDefaultOptions(): array {
    return parent::newDefaultOptions() + [
      'endpoint' => null,
      'encoding' => 'base64',
    ];
  }

  public function newLegacyOptions(): array {
    return parent::newLegacyOptions() + [
      'endpoint' => PhabricatorEnv::getEnvConfig('amazon-ses.endpoint'),
    ];
  }

  public function executeSend($body) {
    // Instead of introducing new config option, we use the endpoint config for
    // @{class:PhabricatorMailImplementationAmazonSESAdapter} to get region
    // information. So it will be eaiser for us to migrate our code later.
    $endpoint = $this->getOption('endpoint');
    $region   = idx(explode('.', $endpoint), 1);

    $client = new SesClient([
      'region'  => $region,
      'version' => 'latest',
    ]);

    return $client->sendRawEmail([
      'RawMessage' => [
        'Data' => $body,
      ],
    ]);
  }

}

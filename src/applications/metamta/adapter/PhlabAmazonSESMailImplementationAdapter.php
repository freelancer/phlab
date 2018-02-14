<?php

/**
 * This mail adapter is similar to
 * @{class:PhabricatorMailImplementationAmazonSESAdapter}, but supports the use
 * of [[http://docs.aws.amazon.com/STS/latest/UsingSTS/Welcome.html |
 * IAM credentials]].
 *
 * This file engine uses [[http://aws.amazon.com/sdk-for-php/ | aws-sdk-php]]
 * to interact with [[http://aws.amazon.com/ | Amazon Web Services]].
 *
 * @todo This class will be obsolete after https://secure.phabricator.com/T5155.
 */
final class PhlabAmazonSESMailImplementationAdapter
  extends PhabricatorMailImplementationPHPMailerLiteAdapter {

  const ADAPTERTYPE = 'aws-ses';

  // We pin the API version so our code will not be affected by a breaking
  // change made to the service
  const API_VERSION = '2010-12-01';

  private $message;
  private $isHTML;

  public function prepareForSend() {
    parent::prepareForSend();
    $this->mailer->Mailer = 'amazon-ses';
    $this->mailer->customMailer = $this;
  }

  public function supportsMessageIDHeader() {
    // Amazon SES will ignore any Message-ID we provide.
    return false;
  }

  protected function validateOptions(array $options) {
    PhutilTypeSpec::checkMap(
      $options,
      [
        'encoding' => 'string',
        'endpoint' => 'string',
      ]);
  }

  public function newDefaultOptions() {
    return parent::newDefaultOptions() + [
      'endpoint' => null,
    ];
  }

  public function newLegacyOptions() {
    return parent::newLegacyOptions() + [
      'endpoint' => PhabricatorEnv::getEnvConfig('amazon-ses.endpoint'),
    ];
  }

  /**
   * @phutil-external-symbol class Aws\Ses\SesClient
   */
  public function executeSend($body) {
    // Instead of introducing new config option, we use the endpoint config for
    // @{class:PhabricatorMailImplementationAmazonSESAdapter} to get region
    // information. So it will be eaiser for us to migrate our code later.
    $endpoint = $this->getOption('endpoint');
    $region   = idx(explode('.', $endpoint), 1);

    $client = new Aws\Ses\SesClient([
      'region'  => $region,
      'version' => self::API_VERSION,
    ]);

    return $client->sendRawEmail([
      'RawMessage' => [
        'Data' => $body,
      ],
    ]);
  }

}

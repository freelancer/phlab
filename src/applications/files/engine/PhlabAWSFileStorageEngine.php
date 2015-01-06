<?php

/**
 * This storage engine is similar to @{class:PhabricatorS3FileStorageEngine},
 * but supports the use of
 * [[http://docs.aws.amazon.com/STS/latest/UsingSTS/Welcome.html |
 * IAM credentials]]. This class will be obsolete after
 * https://secure.phabricator.com/T5155.
 *
 * This file engine uses [[http://aws.amazon.com/sdk-for-php/ | aws-sdk-php]]
 * to interact with [[http://aws.amazon.com/ | Amazon Web Services]].
 */
final class PhlabAWSFileStorageEngine extends PhabricatorFileStorageEngine {

  public function getEngineIdentifier() {
    return 'aws-sdk';
  }

  /**
   * Writes file data into Amazon S3.
   */
  public function writeFile($data, array $params) {
    $s3 = $this->getClient();

    // Generate a random name for this file. We add some directories to it
    // (e.g. 'abcdef123456' becomes 'ab/cd/ef123456') to make large numbers of
    // files more browsable with web/debugging tools like the S3 administration
    // tool.
    $seed = Filesystem::readRandomCharacters(20);
    $parts = array(
      substr($seed, 0, 2),
      substr($seed, 2, 2),
      substr($seed, 4),
    );
    $name = implode('/', $parts);

    $s3_params = array(
      'Bucket'   => $this->getBucketName(),
      'Key'      => $name,
      'Body'     => $data,
      'ACL'      => 'private',

      'Metadata' => array(
        'authorPHID'       => idx($params, 'authorPHID'),
        'isExplicitUpload' => (string) idx($params, 'isExplicitUpload'),
        'name'             => idx($params, 'name'),
      ),
    );

    $mime_type = idx($params, 'mime-type');
    if ($mime_type) {
      $s3_params['ContentType'] = $mime_type;
    }

    AphrontWriteGuard::willWrite();
    $profiler = PhutilServiceProfiler::getInstance();
    $call_id = $profiler->beginServiceCall(
      array(
        'type'   => 's3',
        'method' => 'putObject',
      ));

    $s3->putObject($s3_params);
    $profiler->endServiceCall($call_id, array());

    return $name;
  }

  /**
   * Load a stored blob from Amazon S3.
   */
  public function readFile($handle) {
    $s3 = $this->getClient();

    $profiler = PhutilServiceProfiler::getInstance();
    $call_id = $profiler->beginServiceCall(
      array(
        'type'   => 's3',
        'method' => 'getObject',
      ));

    $result = $s3->getObject(array(
      'Bucket' => $this->getBucketName(),
      'Key'    => $handle,
    ));
    $profiler->endServiceCall($call_id, array());

    return (string) $result['Body'];
  }

  /**
   * Delete a blob from Amazon S3.
   */
  public function deleteFile($handle) {
    $s3 = $this->getClient();

    AphrontWriteGuard::willWrite();
    $profiler = PhutilServiceProfiler::getInstance();
    $call_id = $profiler->beginServiceCall(
      array(
        'type'   => 's3',
        'method' => 'deleteObject',
      ));

    $s3->deleteObject(array(
      'Bucket' => $this->getBucketName(),
      'Key'    => $handle,
    ));
    $profiler->endServiceCall($call_id, array());
  }

  /**
   * Retrieve the S3 bucket name.
   */
  protected function getBucketName() {
    $key    = 'storage.s3.bucket';
    $bucket = PhabricatorEnv::getEnvConfig($key);

    if (!$bucket) {
      throw new PhabricatorFileStorageConfigurationException(
        pht("No '%s' specified!", $key));
    }

    return $bucket;
  }

  /**
   * Create a new S3 API object.
   *
   * @phutil-external-symbol class Aws\S3\S3Client
   */
  protected function getClient() {
    Composer::registerAutoloader();
    return Aws\S3\S3Client::factory();
  }

}

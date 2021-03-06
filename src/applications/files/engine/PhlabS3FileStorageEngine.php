<?php

use Aws\S3\S3Client;

/**
 * This storage engine is similar to @{class:PhabricatorS3FileStorageEngine},
 * but supports the use of
 * [[http://docs.aws.amazon.com/STS/latest/UsingSTS/Welcome.html |
 * IAM credentials]].
 *
 * This file engine uses [[http://aws.amazon.com/sdk-for-php/ | aws-sdk-php]]
 * to interact with [[http://aws.amazon.com/ | Amazon Web Services]].
 *
 * @phutil-external-symbol class S3Client
 *
 * @todo This class will be obsolete after https://secure.phabricator.com/T5155.
 */
final class PhlabS3FileStorageEngine extends PhabricatorFileStorageEngine {

  /**
   * Return a unique string which identifies this storage engine.
   *
   * @return string
   */
  public function getEngineIdentifier(): string {
    return 'aws-s3';
  }

  /**
   * Prioritize this engine relative to other engines.
   *
   * @return float
   */
  public function getEnginePriority(): float {
    return 100;
  }

  /**
   * Return `true` if the engine is currently writable.
   *
   * @return bool
   */
  public function canWriteFiles(): bool {
    try {
      $this->getClient();
      $this->getBucketName();
      return true;
    } catch (Exception $ex) {
      return false;
    }
  }

  /**
   * Writes file data into Amazon S3.
   *
   * Write file data to the backing storage and return a handle which can later
   * be used to read or delete it.
   *
   * @param  string             The file data to write.
   * @param  map<string, wild>  File metadata.
   * @return string             Unique string which identifies the stored file.
   */
  public function writeFile($data, array $params): string {
    $s3 = $this->getClient();

    // Generate a random name for this file. We add some directories to it
    // (e.g. 'abcdef123456' becomes 'ab/cd/ef123456') to make large numbers of
    // files more browsable with web/debugging tools like the S3 administration
    // tool.
    $seed = Filesystem::readRandomCharacters(20);
    $parts = [
      substr($seed, 0, 2),
      substr($seed, 2, 2),
      substr($seed, 4),
    ];
    $name = implode('/', $parts);

    $s3_params = [
      'Bucket'   => $this->getBucketName(),
      'Key'      => $name,
      'Body'     => $data,
      'ACL'      => 'private',
      'Metadata' => [
        'authorPHID'       => idx($params, 'authorPHID'),
        'isExplicitUpload' => (string)idx($params, 'isExplicitUpload'),
        'name'             => rawurlencode(idx($params, 'name')),
      ],
    ];

    $mime_type = idx($params, 'mime-type');
    if ($mime_type) {
      $s3_params['ContentType'] = $mime_type;
    }

    AphrontWriteGuard::willWrite();
    $profiler = PhutilServiceProfiler::getInstance();
    $call_id  = $profiler->beginServiceCall([
      'type'   => 's3',
      'method' => 'putObject',
    ]);

    $s3->putObject($s3_params);
    $profiler->endServiceCall($call_id, []);

    return $name;
  }

  /**
   * Load a stored blob from Amazon S3.
   *
   * @param  string  The handle returned from @{method:writeFile} when the file
   *                 was written.
   * @return string  File contents.
   */
  public function readFile($handle): string {
    $s3 = $this->getClient();

    $profiler = PhutilServiceProfiler::getInstance();
    $call_id  = $profiler->beginServiceCall([
      'type'   => 's3',
      'method' => 'getObject',
    ]);

    $result = $s3->getObject([
      'Bucket' => $this->getBucketName(),
      'Key'    => $handle,
    ]);
    $profiler->endServiceCall($call_id, []);

    return (string)$result['Body'];
  }

  /**
   * Delete a blob from Amazon S3.
   *
   * @param  string  The handle returned from @{method:writeFile} when the file
   *                 was written.
   * @return void
   */
  public function deleteFile($handle): void {
    $s3 = $this->getClient();

    AphrontWriteGuard::willWrite();
    $profiler = PhutilServiceProfiler::getInstance();
    $call_id  = $profiler->beginServiceCall([
      'type'   => 's3',
      'method' => 'deleteObject',
    ]);

    $s3->deleteObject([
      'Bucket' => $this->getBucketName(),
      'Key'    => $handle,
    ]);
    $profiler->endServiceCall($call_id, []);
  }

  /**
   * Retrieve the S3 bucket name.
   *
   * @return  string
   */
  protected function getBucketName(): string {
    $key    = 'storage.s3.bucket';
    $bucket = PhabricatorEnv::getEnvConfig($key);

    if (!$bucket) {
      throw new PhabricatorFileStorageConfigurationException(
        pht(
          "No '%s' specified!",
          $key));
    }

    return $bucket;
  }

  /**
   * Create a new S3 API object.
   *
   * @return S3Client
   */
  protected function getClient(): S3Client {
    $region = PhabricatorEnv::getEnvConfig('amazon-s3.region');

    return new S3Client([
      'region'  => $region,
      'version' => 'latest',
    ]);
  }

}

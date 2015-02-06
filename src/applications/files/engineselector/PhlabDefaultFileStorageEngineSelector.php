<?php

/**
 * Chooses appropriate storage engine(s) for files. When Phabricator needs
 * to write a blob of file data, it uses the configured selector to get a list
 * of suitable @{class:PhabricatorFileStorageEngine}s.
 *
 * @todo This class will be obsolete after https://secure.phabricator.com/D11224.
 */
final class PhlabDefaultFileStorageEngineSelector
  extends PhabricatorFileStorageEngineSelector {

  /**
   * Select viable default storage engines according to configuration.
   *
   * @param  string  File data.
   * @param  dict    Dictionary of optional file metadata.
   * @return list    List of @{class:PhabricatorFileStorageEngine}s, ordered by
   *                 preference.
   */
  public function selectStorageEngines($data, array $params) {
    $engines = array();

    try {
      $original_engines = id(new PhabricatorDefaultFileStorageEngineSelector())
        ->selectStorageEngines($data, $params);
    } catch (Exception $ex) {
      $original_engines = array();
    }

    foreach ($original_engines as $engine) {
      if (!$engine instanceof PhabricatorS3FileStorageEngine) {
        $engines[] = $engine;
      }
    }

    if (PhabricatorEnv::getEnvConfig('storage.s3.bucket')) {
      array_unshift($engines, new PhlabAWSFileStorageEngine());
    }

    return $engines;
  }

}

<?php

final class PhlabDefaultFileStorageEngineSelector
  extends PhabricatorFileStorageEngineSelector {

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

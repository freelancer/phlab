<?php

final class Composer {

  public static function registerAutoloader() {
    $root = PhabricatorEnv::getEnvConfig('phlab.composer-path');
    require_once $root.'/vendor/autoload.php';
  }

}

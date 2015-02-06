<?php

/**
 * A wrapper class for interacting with Composer.
 */
final class Composer {

  /**
   * Register the Composer autoloader.
   *
   * Register the Composer autoloader such that Composer libraries can be
   * autoloaded.
   *
   * @return void
   */
  public static function registerAutoloader() {
    $root = PhabricatorEnv::getEnvConfig('phlab.composer-path');
    require_once $root.'/vendor/autoload.php';
  }

}

<?php

/**
 * A wrapper class for interacting with Composer.
 */
final class Composer extends Phobject {

  /**
   * Register the Composer autoloader.
   *
   * Register the Composer autoloader such that Composer libraries can be
   * autoloaded.
   *
   * @return void
   */
  public static function registerAutoloader() {
    require_once dirname(__FILE__).'/../../vendor/autoload.php';
  }

}

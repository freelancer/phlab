<?php

/**
 * A wrapper class for interacting with Composer.
 */
final class Composer extends Phobject {

  private static $initialized = false;

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

    // Workaround for https://secure.phabricator.com/T1116. See T27208.
    if (!self::$initialized) {
      spl_autoload_register(function() {});
      self::$initialized = true;
    }
  }

}

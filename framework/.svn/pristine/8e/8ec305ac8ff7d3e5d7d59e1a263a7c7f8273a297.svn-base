<?php
/**
* Using Mustache template system. http://mustache.github.io/
*/
namespace Util;

require_once (__DIR__ . '/../ext/Mustache/Autoloader.php');
\Mustache_Autoloader::register();

class Template {
  static $m = null;

  /**
   * Merge the values of the variables into the template.
   * This is using the Mustache template system.
   * @param template The template to merge
   * @param variables The key / value pairs to merge into the template
   * @return The string with the values replaced into the template
   **/
  public static function merge($template, $variables) {
    if (!isset(static::$m)) {
      static::$m = new \Mustache_Engine();
    }
    return static::$m->render($template, $variables);
  }
}

<?php
namespace Util;

class Timer {
  static $timers = array();
  /**
   * Start timer.
   * @param name The name of the timer.
   **/
  public static function start($name = 'DEFAULT') {
    static::$timers[$name] = microtime(true);
  }

  /**
   * Stop timer.
   * @param name The name of the timer.
   * @param loglevel Logger level to log.  Logger::NONE to never log (default).  true for Logger::DEBUG, false for Logger::NONE for legacy compatibility
   **/
  public static function stop($name = 'DEFAULT', $loglevel = Logger::NONE) {
    if ($loglevel === true) {
        $loglevel = Logger::DEBUG;
    } else if ($loglevel === false) {
        $loglevel = Logger::NONE;
    }
    $elapsed = null;
    if (isset(static::$timers[$name])) {
      $elapsed = (microtime(true) - static::$timers[$name]) * 1000;
      unset(static::$timers[$name]);
      if ($loglevel !== Logger::NONE && Logger::isLevel($loglevel)) {
        Logger::write(sprintf('%s elapsed %.2f ms',$name , $elapsed), $loglevel);
      }
    }
    return $elapsed;
  }
  
  /**
   * Get the current split time of the timer.
   * @param name The name of the timer
   * @param loglevel Logger level to log.  Logger::NONE to never log (default).  true for Logger::DEBUG, false for Logger::NONE for legacy compatibility
   * @param message The message to log ([name] [message] [time] ms), default 'split'
   **/
  public static function split($name = 'DEFAULT', $loglevel = Logger::NONE, $message = 'split') {
    if ($loglevel === true) {
        $loglevel = Logger::DEBUG;
    } else if ($loglevel === false) {
        $loglevel = Logger::NONE;
    }
    $elapsed = null;
    if (isset(static::$timers[$name])) {
      $elapsed = (microtime(true) - static::$timers[$name]) * 1000;
      if ($loglevel !== Logger::NONE && Logger::isLevel($loglevel)) {
        Logger::write(sprintf('%s %s %.2f ms',$name, $message , $elapsed), $loglevel);
      }
      
    }
    return $elapsed;
  }
}

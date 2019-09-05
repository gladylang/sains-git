<?php
namespace Util;
use \Data\DAOFactory;

class Logger {
  const NONE   = -1;
  const ERROR  = 0;
  const WARN   = 1;
  const INFO   = 2;
  const DEBUG  = 3;
  const TRACE  = 4;
  
  private static $names  = array(
    self::ERROR => "ERROR",
    self::WARN => "WARN",
    self::INFO => "INFO",
    self::DEBUG =>"DEBUG",
    self::TRACE => "TRACE");

  // Current log level.  Prevents output of messages below this level.
  public static $level = self::ERROR;

  // Whether to output the log messages to the html
  public static $output = false;
  // File path for the log file.
  public static $logpath = null;
  // File name for the log file.  Null to disable saving to file.
  public static $logfile = null;
  // IP list for the html log.  Null to output to all.  Otherwise, set array of IPs.
  public static $ipOutputList = null;

  // Don't log the content of GET / POST parameters with these names
  public static $filterFields = array('password','userPswrd');

  private static $buffer = null;

  /**
  * Initialize the Logger.  Log system variables and start page load timer.
  **/
  public static function init() {
    @register_shutdown_function('\Util\Logger::shutdown_function');

    if (static::isDebug() && isset($_SERVER)
          && isset($_SERVER['REQUEST_METHOD'])
          && isset($_SERVER['HTTP_HOST']) 
          && isset($_SERVER['REQUEST_URI'])) {
      Logger::debug('URI: ' . $_SERVER['REQUEST_METHOD'] . ' ' .$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }

    if (static::isTrace() && isset($_SERVER)) {
      Logger::trace('$_SERVER = ' . print_r($_SERVER,true));
    }

    if (static::isDebug()) {
      if (isset($_GET) && count($_GET) > 0) {
        Logger::debug('$_GET = ' . print_r(static::filter($_GET),true));
      }
      if (isset($_POST) && count($_POST) > 0) {
        Logger::debug('$_POST = ' . print_r(static::filter($_POST),true));
      }
    }

    if (static::isTrace() && isset($_SESSION) && count($_SESSION) > 0) {
      Logger::trace('$_SESSION = ' . print_r($_SESSION,true));
    }

    Timer::start('Total');
  }

  /**
   * Censor all fields in filterFields.
   * @param data The array of key / value pairs to filter.
   * @return The filtered array
   **/
  private static function filter($data) {
    $copy = null;
    if (isset($data) && is_array($data)) {
      foreach (static::$filterFields as $field) {
        if (isset($data[$field])) {
          if (!isset($copy)) {
            $copy = $data;
          }
          $copy[$field] = '********';
        }
      }
    }
    if (isset($copy)) {
      return $copy;
    }
    return $data;
  }

  /**
   * Log error message
   **/
  public static function error($message) {
    static::write($message, self::ERROR);
  }

  /**
   * Check if error level messages should be logged
   **/
  public static function isError() {
    return static::isLevel(self::ERROR);
  }

  /**
   * Log warning message
   **/
  public static function warn($message) {
    static::write($message, self::WARN);
  }

  /**
   * Check if warning level messages should be logged
   **/
  public static function isWarn() {
    return static::isLevel(self::WARN);
  }

  /**
   * Log info message
   **/
  public static function info($message) {
    static::write($message, self::INFO);
  }

  /**
   * Check if info level messages should be logged
   **/
  public static function isInfo() {
    return static::isLevel(self::INFO);
  }

  /**
   * Log debug message
   **/
  public static function debug($message) {
    static::write($message, self::DEBUG);
  }

  /**
   * Check if debug level messages should be logged
   **/
  public static function isDebug() {
    return static::isLevel(self::DEBUG);
  }

  /**
   * Log trace message
   **/
  public static function trace($message) {
    static::write($message, self::TRACE);
  }

  /**
   * Check if trace level messages should be logged
   **/
  public static function isTrace() {
    return  static::isLevel(self::TRACE);
  }

  /**
   * Check logging level
   **/
  public static function isLevel($lvl) {
    return (static::$level >= $lvl);
  }

  /**
   * Add message to log queue
   **/
  public static function write($message,$lvl) {
    if (static::isLevel($lvl)) {
      static::$buffer[] = date('Y-m-d H:i:s') . ' - '
        . static::$names[$lvl] . ' - ' . trim($message);
    }
  }

  public static function sort_time($a, $b) {
    if ($a['time'] == $b['time']) {
      return 0;
    }
    return ($a['time'] < $b['time']) ? -1 : 1;
  }
  /**
   * Flush the log queue.
   * Generates statistics.
   * Save the log message to file / output to a DIV.
   **/
  public static function flush() {
    if (static::isInfo() 
      && (DAOFactory::$totalConnectCount > 0 || DAOFactory::$totalQueryCount > 0) ){
      $dbt = sprintf('%0.2f',DAOFactory::$totalConnectTime + DAOFactory::$totalQueryTime + DAOFactory::$totalTransactionTime);
      $dbc = sprintf('%0.2f',DAOFactory::$totalConnectTime);
      $dbcc = DAOFactory::$totalConnectCount;
      $dbq = sprintf('%0.2f',DAOFactory::$totalQueryTime);
      $dbqc = DAOFactory::$totalQueryCount;
      if (DAOFactory::$totalTransactionStart != DAOFactory::$totalTransactionCount) {
        static::error('Database Transaction Start and End count mismatch.  Start: ' .
          DAOFactory::$totalTransactionStart . '  End: ' . 
          DAOFactory::$totalTransactionCount);
      }
      if (DAOFactory::$totalTransactionCount > 0) {
        $tc = DAOFactory::$totalTransactionCount;
        $tt = sprintf('%0.2f',DAOFactory::$totalTransactionTime);
        static::info("Database Total: $dbt ms [Connection($dbcc): $dbc ms  Query($dbqc): $dbq ms Transaction($tc): $tt ms]");
      } else {
        static::info("Database Total: $dbt ms [Connection($dbcc): $dbc ms  Query($dbqc): $dbq ms]");
      }
      if (DAOFactory::DATABASE_DEBUG && static::isInfo()) {
        uasort(DAOFactory::$queryLog, array('self','sort_time'));
        foreach (DAOFactory::$queryLog as $k => $v) {
          static::info("{$v['class']} Query: $k\r\n Count: {$v['count']} ". ($v['cachehit'] > 0?"CacheHits: {$v['cachehit']} ":'') . 'Elapsed: ' . sprintf('%0.1f',$v['time']) . 'ms' . ($v['count'] > 0 ? ' (Max: ' . sprintf('%0.1f', $v['maxtime']) . 'ms)' : ''));
        }
      }
    }
    Timer::stop('Total', Logger::INFO);
    static::info("Peak memory used: " . memory_get_peak_usage());

    if (isset(static::$buffer) && count(static::$buffer) > 0) {
      if (static::$output && static::checkIpList()) {
        $content  = static::getContentType();
        if (empty($content) || substr_compare($content, 'text/html',0,9) == 0) {
          static::flushHtml();
        }
      }
      if (!empty(static::$logfile)) {
        static::flushFile();
      }
      static::$buffer = array();
    }
  }

  private static function checkIpList() {
    if (isset(static::$ipOutputList)) {
      if (is_array(static::$ipOutputList)) {
        foreach (static::$ipOutputList as $ip) {
          if (static::checkIp($ip)) {
            return true;
          }
        }
      } else {
        return static::checkIp(static::$ipOutputList);
      }
    }
    return false;
  }

  private static function checkIp($ip) {
    if ((isset($_SERVER['REMOTE_ADDR']) && $ip == $_SERVER['REMOTE_ADDR'])
        || (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $ip == $_SERVER['HTTP_X_FORWARDED_FOR'])) {
      return true;
    }
    return false;
  }

  private static function getContentType() {
    $headers = headers_list();
    foreach ($headers as $header) {
      if (substr_compare($header, 'Content-Type: ' ,0,14) == 0) {
        return substr($header, 14);
      }
    }
    return null;
  }

// TODO: Extract the log formatters out so that it can be extended
  private static function flushHtml() {
    echo "<br><div style=\"border: 1px solid #808080; margin:2px; background-color: #FFFFFF; foreground-color: #000000;display:none\" id =\"__framework_log__\">\n",
      "<h3 style=\"cursor: pointer;\" onclick=\"var n = this.parentNode.childNodes; for (var i = 1;i < n.length;i++) { if (n[i].nodeName === 'DIV') { if (n[i].style.display === 'none') {n[i].style.display = 'block';} else {n[i].style.display = 'none';}}}\">Log</h3>\n";
    foreach (static::$buffer as $line) {
      echo '<div style="text-align:left; border:1px dotted #808080;',
        ' margin:2px; background-color:#E8FFE8; color:#000000;"><pre style="overflow:auto;">',
        Text::escapeHtml($line),
        "</pre></div>\n";
    }
    echo "</div><script>window.onkeypress = function(e) { var key = e.keyCode ? e.keyCode : e.which; if (e.shiftKey && key == 123) { var elem = document.getElementById('__framework_log__'); if (elem.style.display == 'none') { elem.style.display = 'block'; } else { elem.style.display = 'none'; } e.preventDefault(); }}; </script>\n";
  }

  private static function flushFile() {
    if (!empty(static::$logpath)) {
      $ch = substr(static::$logpath, -1);
      if ($ch != '/' && $ch != '\\') {
        $path = static::$logpath . '/' . static::$logfile;
      } else {
        $path = static::$logpath . static::$logfile;
      }
    } else {
      $path = static::$logfile;
    }
    $file = fopen($path,'ab');
    if ($file !== false) {
      flock($file, LOCK_EX);
      fwrite($file, "------------------------------------------------------------------------\r\n");
      foreach (static::$buffer as $line) {
        if ($file != null) {
          fwrite($file, $line . "\r\n");
        }
      }
      fclose($file);
    }
  }

  /**
   * Shutdown function - to flush the logs after the script ends.
   **/
  public static function shutdown_function() {
    $error = \error_get_last();

    if( $error !== NULL && $error['type'] === E_ERROR) {
      Logger::error('Fatal Error: '  . $error['message']
                  . "\r\nFile: " . $error['file'] . '  Line: ' . $error['line']);
    }
    static::flush();
  }
}

<?php
namespace Data;
use Util\Logger;
use Util\Timer;

class DAOFactory {
  const DEFAULT_DATABASE = 'DEFAULT';
  const DATABASE_DEBUG = true;
  protected static $databases = array();
  protected static $register = array();
  
  public static $queryLog = array();
  public static $totalQueryTime = 0;
  public static $totalQueryCount = 0;
  public static $totalConnectTime = 0;
  public static $totalConnectCount = 0;
  public static $totalTransactionTime = 0;
  public static $totalTransactionStart = 0;
  public static $totalTransactionCount = 0;
  protected static $cache_enabled = false;
  
  public static function setCacheEnabled($cache) {
    static::$cache_enabled = $cache;
  }

  public static function addQueryLog($class, $query, $time = 0, $cached = false) {
    if (Logger::isDebug() || (DAOFactory::DATABASE_DEBUG && Logger::isInfo())) {
      if (!$cached) {
        static::$totalQueryTime += $time;
        static::$totalQueryCount++;
      }
    }
    if (DAOFactory::DATABASE_DEBUG) {
      if (!array_key_exists($query, static::$queryLog)) {
        static::$queryLog[$query] =  array('count' => 0, 'time' => 0, 'cachehit' => 0, 'maxtime' => 0);
      }
      static::$queryLog[$query]['count']++;
      static::$queryLog[$query]['time']+= $time;
      static::$queryLog[$query]['class'] = $class;
      if ($time > static::$queryLog[$query]['maxtime']) {
        static::$queryLog[$query]['maxtime'] = $time;
      }
      if ($cached) {
        static::$queryLog[$query]['cachehit']++;
      }
    }
  }

  protected static $dsnTable = array('sqlite' => 'SQLite', 'mysql' => 'MySql', 'pgsql' => 'PgSql', 'mssql' => 'MsSql', 'sqlsrv' =>'MsSql', 'odbc' => 'MsSql', 'dblib' => 'MsSql', 'oci' => 'Oracle');

  public static function setDatabase($dsn, $username, $password, $databaseName = self::DEFAULT_DATABASE) {
    $type = strstr($dsn,':',true);
    if (empty($type)) {
      throw new DAOException('Invalid database type');
    }
    $value = array();
    if (array_key_exists($type,static::$dsnTable)) {
      $value['Type'] = static::$dsnTable[$type];
    } else {
      $value['Type'] = 'Unknown';
      Logger::error('Unknown database type in DSN: ' . $type);
    }
    $value['DSN'] = $dsn;
    $value['Username'] = $username;
    $value['Password'] = $password;
    $value['DBH'] = null;
    $value['DBUtil'] = null;

    static::$databases[$databaseName] = $value;
  }

  private static function getDB($databaseName = self::DEFAULT_DATABASE) {
    if (!isset(static::$databases[$databaseName])) {
      Logger::error("Unknown database connection $databaseName");
      return null;
    }
    $db = static::$databases[$databaseName];
    if (!isset($db['DBH'])) {
      if (Logger::isDebug() || (DAOFactory::DATABASE_DEBUG && Logger::isInfo())) {
        Timer::start("Connecting to database $databaseName");
      }
      if (Logger::isTrace()) {
        Logger::trace("Database connection $databaseName - {$db['DSN']}");
      }
      $db['Name'] = $databaseName;
      try {
        $db['DBH'] = new \PDO($db['DSN'], $db['Username'], $db['Password'], array(\PDO::ATTR_CASE => \PDO::CASE_LOWER));
        $db['DBH']->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $classname = '\\Data\\'. $db['Type'] . 'DatabaseUtil';
        if (!class_exists($classname)) {
          $classname = '\\Data\\GenericDatabaseUtil';
        }
        $db['DBUtil'] = new $classname();
        $db['DBUtil'] ->setPDO($db['DBH']);
        if (Logger::isDebug() || (DAOFactory::DATABASE_DEBUG && Logger::isInfo())) {
          static::$totalConnectTime += Timer::stop("Connecting to database $databaseName", Logger::DEBUG);
          static::$totalConnectCount++;
        }
        static::$databases[$databaseName] = $db;
      } catch (\Exception $e) {
        throw new DAOException("Failed to connect to database ".$databaseName.".",0,$e);
      }
    }
    return $db;
  }

  public static function getPDO($databaseName = self::DEFAULT_DATABASE) {
    $db = static::getDB($databaseName);
    return isset($db)?$db['DBH']:null;
  }
  
  public static function getDatabaseUtil($databaseName = self::DEFAULT_DATABASE) {
    $db = static::getDB($databaseName);
    return isset($db)?$db['DBUtil']:null;
  }

  public static function getDatabaseType($databaseName = self::DEFAULT_DATABASE) {
    $db = static::getDB($databaseName);
    return isset($db)?$db['Type']:null;
  }

  public static function beginTransaction($databaseName = self::DEFAULT_DATABASE) {
    $dbh = static::getPDO($databaseName);
    Logger::debug('Begin Transaction on ' . $databaseName);
    if (!$dbh->beginTransaction()) {
      throw new DAOException("Failed to begin transaction");
    }
    static::$totalTransactionStart++;
  }

  public static function commit($databaseName = self::DEFAULT_DATABASE) {
    $dbh = static::getPDO($databaseName);
    if (Logger::isDebug()) {
      Timer::start("Commit Transaction on " . $databaseName);
    }
    $success = $dbh->commit();
    if (Logger::isDebug()) {
      static::$totalTransactionTime += Timer::stop("Commit Transaction on " . $databaseName, Logger::DEBUG);
      static::$totalTransactionCount++;
    }
    if (!$success) {
      throw new DAOException("Failed to commit transaction");
    }
  }

  public static function rollback($databaseName = self::DEFAULT_DATABASE) {
    $dbh = static::getPDO($databaseName);
    if (Logger::isDebug()) {
      Timer::start("Rollback Transaction on " . $databaseName);
    }
    $success = $dbh->rollback();
    if (Logger::isDebug()) {
      static::$totalTransactionTime += Timer::stop("Rollback Transaction on " . $databaseName, Logger::DEBUG);
      static::$totalTransactionCount++;
    }
    if (!$success) {
      throw new DAOException("Failed to rollback transaction");
    }
  }

  public static function getDAO($type, $databaseName=null) {
    if (!isset($databaseName)) {
      $daotype = 'Data\\' . $type . 'DAO';
      if (isset($daotype::$DEFAULT_DATABASE)) {
        $databaseName = $daotype::$DEFAULT_DATABASE;
      } else {
        $databaseName = self::DEFAULT_DATABASE;
      }
    }
    $registerKey = $databaseName . '::' . $type;
    if (!isset(static::$register[$registerKey])) {
      $db = static::getDB($databaseName);
      $daotype = 'Data\\' . $db['Type'] . $type . 'DAO';
      if (!class_exists($daotype)) {
        $daotype = 'Data\\' . $type . 'DAO';
      }
      $dao = new $daotype();
      if (isset($dao)) {
        $dao->setDB($db);
        $dao->setCacheEnabled(static::$cache_enabled);
        static::$register[$registerKey] = $dao;
      }
    }
    if (isset(static::$register[$registerKey])) {
      return static::$register[$registerKey];
    } else {
      throw new DAOException("Failed to initialize DAO");
    }
  }
}

<?php
namespace Data;
use Util\Timer;
use Util\Logger;

abstract class BaseDAO {
  static $DEFAULT_DATABASE = DAOFactory::DEFAULT_DATABASE;
  protected $db;
  public $dbutil;
  const MAX_BLOB_READ = 16777216;
  protected $statement_cache = array();
  protected $data_cache = array();
  protected $cache_enabled = false;

  public function setCacheEnabled($cache) {
    $this->cache_enabled = $cache;
  }
  public function setDB($db) {
    $this->db = $db;
    $this->dbutil = $db['DBUtil'];
  }

  public function escapeString($string) {
    return $this->dbutil->escapeString($string);
  }

  public function formatDate($date) {
    return $this->dbutil->formatDate($date);
  }

  public function getDateFormat() {
    return $this->dbutil->getDateFormat();
  }
  
  public function getDateTimeFormat() {
    return $this->dbutil->getDateTimeFormat();
  }

  public function formatDateTime($datetime) {
    return $this->dbutil->formatDateTime($datetime);
  }

  protected function getCallingFunction() {
    if (Logger::isTrace()) {
        $r = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,3);
        $r = $r[2];
        $name = $r['class'] . '->' . $r['function'];
    } else {
        $name = get_class($this);
    }
    $i = strpos($name, 'Data\'');
    if ($i >= 0) {
        $name = substr($name, $i + 5);
    }
    return $name;
  }

  public static function formatPDOParamType($type) {
    if (($type & \PDO::PARAM_INPUT_OUTPUT) == \PDO::PARAM_INPUT_OUTPUT) {
      $type &= (~\PDO::PARAM_INPUT_OUTPUT);
      $r = 'InputOutput ';
    } else {
      $r = '';
    }
    switch ($type) {
      case \PDO::PARAM_BOOL:
        return $r.'BOOL';
      case \PDO::PARAM_NULL:
        return $r.'NULL';
      case \PDO::PARAM_INT:
        return $r.'INT';
      case \PDO::PARAM_STR:
        return $r.'STR';
      case \PDO::PARAM_LOB:
        return $r.'BLOB';
      default:
        return $type;
    }
  }

  protected function prepareStatement($query, $params = null, $paramTypes = null,$driver_options = null) {
    if (!is_null($driver_options)) {
      $key = $query . "_" . serialize($driver_options);
      if (!array_key_exists($key,$this->statement_cache)) {
        $this->statement_cache[$key] = $this->db['DBH']->prepare($query, $driver_options);
        if (Logger::isTrace()) {
          Logger::trace("prepareStatement: '$query'");
        }
      } else {
        if (Logger::isTrace()) {
          Logger::trace("prepareStatement cached: '$query'");
        }
      }
    } else {
      $key = $query;
      if (!array_key_exists($key, $this->statement_cache)) {
        $this->statement_cache[$key] = $this->db['DBH']->prepare($query);
        if (Logger::isTrace()) {
          Logger::trace("prepareStatement: '$query'");
        }
      } else {
        if (Logger::isTrace()) {
          Logger::trace("prepareStatement cached: '$query'");
        }
      }
    }
    $stmt = $this->statement_cache[$key];
    if (!is_null($params)) {
      foreach ($params as $key => $value) {
        if (!is_null($paramTypes) && array_key_exists($key,$paramTypes)) {
          $stmt->bindValue($key,$value,$paramTypes[$key]);
          if (Logger::isTrace()) {
            if ($paramTypes[$key] == \PDO::PARAM_LOB) {
              Logger::trace("Bind Typed[".static::formatPDOParamType($paramTypes[$key])."] Value: $key Length = " . strlen($value));
            } else {
              Logger::trace("Bind Typed[".static::formatPDOParamType($paramTypes[$key])."] Value: $key = '$value'");
            }
          }
        } else {
          $stmt->bindValue($key,$value);
          if (Logger::isTrace()) {
            if (!isset($value)) {
              Logger::trace("Bind Value: $key = NULL");
            } else {
              Logger::trace("Bind Value: $key = '$value'");
            }
          }
        }
      }
    }
    return $stmt;
  }

  protected function executeGetRows($query, $params = null, $paramTypes = null, $cache = null) {
    if (Logger::isInfo()) {
      Timer::start("executeGetRows: '$query' DB: " . $this->db['Name']);
    }
    if ($cache == null) {
      $cache = $this->cache_enabled;
    }
    $cache_key = null;
    if ($cache) {
      $cache_key = sha1('executeGetRows:' . $query  . '_params:' . serialize($params));
      if (array_key_exists($cache_key, $this->data_cache)) {
        $qtime = Timer::stop("executeGetRows: '$query' DB: " . $this->db['Name']);
        DAOFactory::addQueryLog($this->getCallingFunction(), $query, $qtime, true);
        return $this->data_cache[$cache_key];
      }
    }
    $stmt = $this->prepareStatement($query, $params, $paramTypes);
    $error = null;
    $return = null;
    if ($stmt->execute()) {
      $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      foreach ($return as $rk => $row) {
        foreach ($row as $key => $value) {
          if (is_resource($value)) {
            $return[$rk][$key] = fread($value, self::MAX_BLOB_READ);
          }
        }
      }
    } else {
      $error = $stmt->errorInfo();
    }
    $stmt->closeCursor();
    unset($stmt);
    if (Logger::isInfo()) {
      $qtime = Timer::stop("executeGetRows: '$query' DB: " . $this->db['Name'], Logger::DEBUG);
      DAOFactory::addQueryLog($this->getCallingFunction(), $query, $qtime);
    }
    if ($cache && isset($cache_key)) {
      $this->data_cache[$cache_key] = $return;
    }
    if (!is_null($error)) {
      Logger::error("failed executeGetRows: '$query' info: " . $error);
      throw new DAOException($error);
    } else {
      return $return;
    }
  }

  protected function executeGetSingleRow($query, $params = null, $paramTypes = null, $cache = null) {
    if (Logger::isInfo()) {
      Timer::start("executeGetSingleRow: '$query' DB: " . $this->db['Name']);
    }
    if ($cache == null) {
      $cache = $this->cache_enabled;
    }
    $cache_key = null;
    if ($cache) {
      $cache_key = sha1('executeGetSingleRow:' . $query  . '_params:' . serialize($params));
      if (array_key_exists($cache_key, $this->data_cache)) {
        $qtime = Timer::stop("executeGetSingleRow: '$query' DB: " . $this->db['Name'] );
        DAOFactory::addQueryLog($this->getCallingFunction(), $query, $qtime, true);
        return $this->data_cache[$cache_key];
      }
    }
    $stmt = $this->prepareStatement($query, $params, $paramTypes, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
    $error = null;
    $row = null;
    if ($stmt->execute()) {
      $row = $stmt->fetch(\PDO::FETCH_ASSOC);
      if (!is_null($row) && is_array($row)) {
        foreach ($row as $key => $value) {
          if (is_resource($value)) {
            $row[$key] = fread($value, self::MAX_BLOB_READ);
          }
        }
      } else {
        $row = null;
      }
    } else {
      $error = $stmt->errorInfo();
    }
    $stmt->closeCursor();
    unset($stmt);
    if (Logger::isInfo()) {
      $qtime = Timer::stop("executeGetSingleRow: '$query' DB: " . $this->db['Name'], Logger::DEBUG);
      DAOFactory::addQueryLog($this->getCallingFunction(), $query, $qtime);
    }
    if ($cache && isset($cache_key)) {
      $this->data_cache[$cache_key] = $row;
    }
    if (!is_null($error)) {
      Logger::error("failed executeGetSingleRow: '$query' info: " . $error);
      throw new DAOException($error);
    } else {
      return $row;
    }
  }

  protected function executeGetSingleValue($query, $params = null, $paramTypes = null, $cache = null) {
    $row = $this->executeGetSingleRow($query, $params, $paramTypes, $cache);
    if (!is_null($row) && is_array($row) && count($row) > 0) {
      reset($row);
      return $row[key($row)]; 
    } else {
      return null;
    }
  }

  protected function executeNonQuery($query, $params = null, $paramTypes = null) {
    if (Logger::isInfo()) {
      Timer::start("executeNonQuery: '$query' DB: " . $this->db['Name']);
    }
    $stmt = $this->prepareStatement($query, $params, $paramTypes);
    $error = null;
    $rowsAffected = -1;
    if ($stmt->execute()) {
      $rowsAffected = $stmt->rowCount();
    } else {
      $error = $stmt->errorInfo();
    }
    $stmt->closeCursor();
    unset($stmt);
    if (Logger::isInfo()) {
      $qtime = Timer::stop("executeNonQuery: '$query' DB: " . $this->db['Name'], Logger::DEBUG);
      DAOFactory::addQueryLog($this->getCallingFunction(), $query, $qtime);
    }
    if (!is_null($error)) {
      Logger::error("failed executeGetRows: '$query' info: " . $error);
      throw new DAOException($error);
    }
    return $rowsAffected;
  }

  protected function executeRowCallback($query, \Closure $callback, $params = null, $paramTypes = null) {
    if (Logger::isInfo()) {
      Timer::start("executeRowCallback: '$query' DB: " . $this->db['Name']);
    }
    $stmt = $this->prepareStatement($query, $params, $paramTypes, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
    $error = null;
    $row = null;
    if ($stmt->execute()) {
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!is_null($row) && is_array($row)) {
          foreach ($row as $key => $value) {
            if (is_resource($value)) {
              $row[$key] = fread($value, self::MAX_BLOB_READ);
            }
          }
          $callback($row);
        }
        $row = null;
      }
    } else {
      $error = $stmt->errorInfo();
    }
    $stmt->closeCursor();
    unset($stmt);
    if (Logger::isInfo()) {
      $qtime = Timer::stop("executeRowCallback: '$query' DB: " . $this->db['Name'], Logger::DEBUG);
      DAOFactory::addQueryLog($this->getCallingFunction(), $query, $qtime);
    }
    if (!is_null($error)) {
      Logger::error("failed executeRowCallback: '$query' info: " . $error);
      throw new DAOException($error);
    }
  }

  protected function lastInsertId($name = null) {
    return $this->db['DBH']->lastInsertId($name);
  }
}

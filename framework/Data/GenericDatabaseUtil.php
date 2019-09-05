<?php
namespace Data;

class GenericDatabaseUtil extends BaseDatabaseUtil implements DatabaseUtil {
  public function getDateFormat() {
    return 'Y-m-d';
  }
  
  public function getDateTimeFormat() {
    return 'Y-m-d H:i:s';
  }

  public function formatDate($time) {
    if (!isset($time)) {
      return null;
    }
    $format = $this->getDateFormat();
    if ($time instanceof \DateTime) {
      return $time->format($format);
    }
    return date($format,$time);
  }
  
  public function formatDateTime($time) {
    if (!isset($time)) {
      return null;
    }
    $format = $this->getDateTimeFormat();
    if ($time instanceof \DateTime) {
      return $time->format($time);
    }
    return date($this->getDateTimeFormat(),$time);
  }
  
  public function escapeString($string) {
    return $this->db->quote($string);
  }
  
  public function escapeImplode($pieces, $quote = '\'', $glue = ', ') {
    $string = '';
    foreach ($pieces as $piece) {
      if ($string != '') {
        $string .= $glue;
      }
      $string .= $quote . $this->escapeString($piece) . $quote;
    }
    return $string;
  }
  
  public function delimitIdentifier($identifier) {
    return '"' . $identifier . '"';
  }
}

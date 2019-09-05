<?php
namespace Mail;

class MailerFactory {
  protected static $_instance = null;
  protected static $_config = null;

  public static function setConfig($config) {
    static::$_config = $config;
  }

  public static function createInstance($config = null) {
    if (!isset($config)) {
      $config = static::$_config;
    }
    if (isset($config)) {
      if (isset($config['type'])) {
        $type = $config['type'];
      } else {
        $type=  'Swift';
      }
    } else {
      $type = 'Null';
    }
    $type = 'Mail\\' . $type . 'Mailer';
    static::$_instance = new $type($config);
    return static::$_instance;
  }
  
  public static function getInstance($config = null) {
    if (static::$_instance  == null) {
      return static::createInstance($config);
    }
    return static::$_instance;
  }
}
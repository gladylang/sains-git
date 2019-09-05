<?php
namespace Authentication;

class AuthenticatorFactory {
  protected static $authType = null;
  protected static $authConfig = null;
  
  public static function setAuthenticator($type,$config = null) {
    static::$authType = $type;
    static::$authConfig = $config;
  }

  public static function getAuthenticator($type = null,$config = null) {
    if ($type == null) {
      $type = static::$authType;
    }
    if ($config == null) {
      $config = static::$authConfig;
    }
    $type = '\\Authentication\\' . $type . 'Authenticator';
    return new $type($config);
  }
}

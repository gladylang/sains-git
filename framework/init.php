<?php
include_once(__DIR__ . '/autoloader.php');
define ('FRAMEWORK_VERSION','0.9.31');

try {
  if (is_array($db_dsn)) {
    foreach ($db_dsn as $key => $dsn) {
      Data\DAOFactory::setDatabase($db_dsn[$key],$db_username[$key],$db_password[$key],$key);
    }
  } else if (isset($db_dsn)) {
    Data\DAOFactory::setDatabase($db_dsn,$db_username,$db_password);
  }
} catch (Exception $e) {
  die('Failure establishing connection to database.');
}

if (isset($authenticator)) {
  Authentication\AuthenticatorFactory::setAuthenticator($authenticator,$authenticatorConfig);
}

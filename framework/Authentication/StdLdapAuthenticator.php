<?php
namespace Authentication {
  use \Util\Logger;
    
  class StdLdapAuthenticator implements Authenticator {
    var $ldapUrl = null;
    var $ldapPort = 389;
    var $ldapBaseDn = '';
    var $ldapBindDn = null;
    var $ldapBindPassword = '';
    var $db = 'DEFAULT';
    var $daoName = null;
    var $throwException = false;
    
    public function __construct($config = null) {
      if (isset($config) && is_array($config)) {
        if (isset($config['Url'])) {
          $this->ldapUrl = $config['Url'];
        }
        if (isset($config['Port'])) {
          $this->ldapPort = $config['Port'];
        }
        if (isset($config['BaseDN'])) {
          $this->ldapBaseDn = $config['BaseDN'];
        }
        if (!empty($config['BindDN'])) {
          $this->ldapBindDn = $config['BindDN'];
          if (isset($config['BindPassword'])) {
            $this->ldapBindPassword = $config['BindPassword'];
          }
        }
        if (!empty($config['Filter'])) {
          $this->ldapFilter = $config['Filter'];
        } else {
            $searchkey = 'uid';
            if (!empty($config['SearchKey'])) {
              $searchkey = $config['SearchKey'];
            }
            $this->ldapFilter = $searchkey . '=$username';
        }
        if (!empty($config['DB'])) {
          $this->db = $config['DB'];
          $this->daoName = 'User';
        }
        if (!empty($config['DaoName'])) {
          $this->daoName = $config['DaoName'];
        }
        if (isset($config['ThrowException'])) {
          $this->throwException = $config['ThrowException'];
        }
      }
    }
    
    public function authenticate($username, $password) {
      return $this->authenticateLdap($username, $password)
        || $this->authenticateDatabase($username, $password);
    }

    public function authenticateDatabase($username, $password) {
      if (isset($this->daoName)) {
        $dao = \Data\DAOFactory::getDAO($this->daoName,$this->db);
        $user = $dao->getUserByUsername($username);
        if ($user != null) {
          if (\Util\Hash::validateHash($password, $user['password'])) {
            return true;
          } else {
            Logger::debug('StdLdapAuthenticator: Failed to authenticate user with DB.');
          }
        } else {
          Logger::debug('StdLdapAuthenticator: Failed to find user in DB.');
        }
      }
      return false;
    }
    
    public function authenticateLdap($username, $password) {
      if (isset($this->ldapUrl)) {
        $v_ds=ldap_connect($this->ldapUrl, $this->ldapPort);
        @ldap_set_option($v_ds, LDAP_OPT_REFERRALS, 0);
        @ldap_set_option($v_ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        if (isset($this->ldapBindDn)) {
          if (@ldap_bind( $v_ds, $this->ldapBindDn, $this->ldapBindPassword) === FALSE) {
            Logger::error("StdLdapAuthenticator: Failed to bind to LDAP server. " . ldap_error($v_ds));
            if ($this->throwException) {
              throw new AuthenticationException("Failed to query LDAP server.");
            }
            return false;
          }
        }

        $filter = strtr($this->ldapFilter, array('$username' => ldap_escape($username, null, LDAP_ESCAPE_FILTER)));
        $v_r=@ldap_search($v_ds, $this->ldapBaseDn, $filter);
        if ($v_r === FALSE) {
          Logger::error("StdLdapAuthenticator: Failed to search LDAP server. " . ldap_error($v_ds));
          if ($this->throwException) {
            throw new AuthenticationException("Failed to query LDAP server.");
          }
          return false;
        } else {
          $v_result = @ldap_get_entries( $v_ds, $v_r);
        }
        if (isset($v_result) && isset($v_result[0]) && $v_result[0]) {
          $result = @ldap_bind( $v_ds, $v_result[0]['dn'], $password);
          @ldap_unbind($v_ds);
          if ($result) {
            return true;
          } else {
            Logger::debug('StdLdapAuthenticator: Failed to authenticate user with LDAP.');
            return false;
          }
        } else {
          Logger::debug('StdLdapAuthenticator: Failed to find user in LDAP.');
        }
      }
      return false;
    }

    public function setPassword($username, $password) {
    // Only updates the local user password
      if (isset($this->daoName)) {
        $dao = \Data\DAOFactory::getDAO($this->daoName,$this->db);
        $user = $dao->getUserByUsername($username);
        if ($user != null) {
          $user = $dao->updateUser($username, \Util\Hash::bcrypt($password));
        } else {
          throw new AuthenticationException("User not found");
        }
      }
    }

    public function addUser($username, $password) {
      // Only adds the local user
      if (isset($this->daoName)) {
        $dao = \Data\DAOFactory::getDAO($this->daoName,$this->db);
        $user = $dao->createUser($username,\Util\Hash::bcrypt($password));
      }
    }

    public function removeUser($username) {
      // Only removes the local user
      if (isset($this->daoName)) {
        $dao = \Data\DAOFactory::getDAO($this->daoName,$this->db);
        $dao->deleteUser($username);
      }
    }

    public function userExists($username) { 
      if (isset($this->ldapUrl)) {
        $v_ds=ldap_connect($this->ldapUrl, $this->ldapPort);
        @ldap_set_option($v_ds, LDAP_OPT_REFERRALS, 0);
        @ldap_set_option($v_ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        if (isset($this->ldapBindDN)) {
          if (!@ldap_bind( $v_ds, $this->ldapBindDn, $this->ldapBindPassword)) {
            Logger::error("Failed to bind to LDAP server. " . ldap_error($v_ds));
            if ($this->throwException) {
              throw new AuthenticationException("Failed to query LDAP server.");
            }
            return false;
          }
        }
        $filter = strtr($this->ldapFilter, array('$username' => ldap_escape($username, null, LDAP_ESCAPE_FILTER)));
        $v_r=@ldap_search($v_ds, $this->ldapBaseDn, $filter);
        if ($v_r === FALSE) {
          Logger::error("Failed to search LDAP server. " . ldap_error($v_ds));
          if ($this->throwException) {
            throw new AuthenticationException("Failed to query LDAP server.");
          }
          return false;
        }
        if ($v_r){
          $v_result = @ldap_get_entries( $v_ds, $v_r);
          if (isset($v_result[0]) && $v_result[0]) {
            return true;
          }
        }
      }
      if (isset($this->daoName)) {
        $dao = \Data\DAOFactory::getDAO($this->daoName,$this->db);
        $user = $dao->getUserByUsername($username);
        if ($user != null) {
          return true;
        }
      }
      return false;
    }
  }
}

namespace {
  if (!function_exists('ldap_escape')) {
      define('LDAP_ESCAPE_FILTER', 0x01);
      define('LDAP_ESCAPE_DN',     0x02);

      /**
       * @param string $subject The subject string
       * @param string $ignore Set of characters to leave untouched
       * @param int $flags Any combination of LDAP_ESCAPE_* flags to indicate the
       *                   set(s) of characters to escape.
       * @return string
       */
      
      function ldap_escape($subject, $ignore = '', $flags = 0) {
          static $charMaps = array(
              LDAP_ESCAPE_FILTER => array('\\', '*', '(', ')', "\x00"),
              LDAP_ESCAPE_DN     => array('\\', ',', '=', '+', '<', '>', ';', '"', '#'),
          );

          // Pre-process the char maps on first call
          if (!isset($charMaps[0])) {
              $charMaps[0] = array();
              for ($i = 0; $i < 256; $i++) {
                  $charMaps[0][chr($i)] = sprintf('\\%02x', $i);;
              }

              for ($i = 0, $l = count($charMaps[LDAP_ESCAPE_FILTER]); $i < $l; $i++) {
                  $chr = $charMaps[LDAP_ESCAPE_FILTER][$i];
                  unset($charMaps[LDAP_ESCAPE_FILTER][$i]);
                  $charMaps[LDAP_ESCAPE_FILTER][$chr] = $charMaps[0][$chr];
              }

              for ($i = 0, $l = count($charMaps[LDAP_ESCAPE_DN]); $i < $l; $i++) {
                  $chr = $charMaps[LDAP_ESCAPE_DN][$i];
                  unset($charMaps[LDAP_ESCAPE_DN][$i]);
                  $charMaps[LDAP_ESCAPE_DN][$chr] = $charMaps[0][$chr];
              }
          }

          // Create the base char map to escape
          $flags = (int)$flags;
          $charMap = array();
          if ($flags & LDAP_ESCAPE_FILTER) {
              $charMap += $charMaps[LDAP_ESCAPE_FILTER];
          }
          if ($flags & LDAP_ESCAPE_DN) {
              $charMap += $charMaps[LDAP_ESCAPE_DN];
          }
          if (!$charMap) {
              $charMap = $charMaps[0];
          }

          // Remove any chars to ignore from the list
          $ignore = (string)$ignore;
          for ($i = 0, $l = strlen($ignore); $i < $l; $i++) {
              unset($charMap[$ignore[$i]]);
          }

          // Do the main replacement
          $result = strtr($subject, $charMap);

          // Encode leading/trailing spaces if LDAP_ESCAPE_DN is passed
          if ($flags & LDAP_ESCAPE_DN) {
              if ($result[0] === ' ') {
                  $result = '\\20' . substr($result, 1);
              }
              if ($result[strlen($result) - 1] === ' ') {
                  $result = substr($result, 0, -1) . '\\20';
              }
          }

          return $result;
      }
  }
}

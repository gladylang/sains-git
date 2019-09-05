<?php
namespace Util;

class Hash {

  /**
   * Generate a hash of the password
   * @param value The password to hash
   * @return The hashed password
   **/
  public static function bcrypt($value,$strength = '10') {
    if (CRYPT_BLOWFISH != 1) {
      throw new UtilException('BCrypt not supported.');
    }

    // May not strictly be bcrypt in future, but password_hash will generate a better hash
    if (version_compare(PHP_VERSION, '5.5.0') >= 0
      && function_exists('password_hash')) {
      return password_hash($value,PASSWORD_DEFAULT);
    }
    
    if (version_compare(PHP_VERSION, '5.3.7') >= 0) {
      $prefix = '$2y$';
    } else {
      $prefix = '$2a$';
    }
    
    $salt = substr(str_replace('+', '.', base64_encode(pack('N4', mt_rand(), mt_rand(), mt_rand(), mt_rand()))), 0, 22);
    return crypt($value, $prefix . $strength . '$' . $salt);
  }
  
  /**
   * validate that the hash is generated from the provided value.
   * The hash is salted, so have to use this to validate the value.
   * @param value The password to validate
   * @param hash The stored hash to validate against
   * @return true if the password matches the hash
   **/
  public static function validateHash($value, $hash) {
    // May not strictly be bcrypt in future, but password_hash will generate a better hash
    if (version_compare(PHP_VERSION, '5.5.0') >= 0
      && function_exists('password_verify')) {
      return password_verify($value,$hash);
    }
    
    return ($hash == crypt($value, $hash));
  }
}
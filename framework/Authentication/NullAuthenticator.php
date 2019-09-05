<?php
namespace Authentication;

class NullAuthenticator implements Authenticator {
  public function authenticate($username, $password) {
    return $username;
  }

  public function userExists($username) {
    return $username;
  }

  public function addUser($username, $password) {}
  public function setPassword($username, $password) {}
  public function removeUser($username) {}
}

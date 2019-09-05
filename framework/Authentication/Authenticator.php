<?php
namespace Authentication;

interface Authenticator {
  // return username if authenticated
  public function authenticate($username, $password);
  
  // return username if exists
  public function userExists($username);
  
  public function addUser($username, $password);
  public function setPassword($username, $password);
  public function removeUser($username);
}

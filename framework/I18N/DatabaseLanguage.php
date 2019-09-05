<?php
namespace I18N;

class DatabaseLanguage extends DefaultLanguage {
  private $dao;
  private $daoName;
  private $databaseName;
  public function __construct($lang, $daoName = 'Lang', $loadAll = false, $databaseName = 'DEFAULT') {
    parent::__construct($lang);
    $this->daoName = $daoName;
    $this->databaseName = $databaseName;
    if ($loadAll) {
      $this->dao = \Data\DAOFactory::getDao($daoName, $databaseName);
      $rows = $this->dao->getAll($lang); 
      foreach ($rows as $row) {
        if (!isset($this->{$row['key']})) {
          $this->{$row['key']} = array();
        }
        $this->{$row['key']}[$row['lang']] = $row['value'];
      }
    }
  }

  public function getValueImpl($key) {
    $lang = $this->lang;
    if (!isset($this->$key)) {
      if ($this->dao == null) {
        $this->dao = \Data\DAOFactory::getDao($this->daoName, $this->databaseName);
      }
      $value = $this->dao->get($lang, $key);
      $this->$key = array();
      $this->{$key}[$lang] = $value;
    }
    if (isset($this->$key) && isset($this->{$key}[$lang])) {
      return $this->{$key}[$lang];
    }
    return null;
  }
}
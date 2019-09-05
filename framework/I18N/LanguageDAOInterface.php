<?php
namespace I18N;

interface LanguageDAOInterface {
  /**
   * Get all text as 
   * return array(array('key'=>$key,'lang'=>$lang),...)
   */
  public function getAll($lang);
  /**
   * Get text for $key, $lang
   * return single string value
   */  
  public function get($lang, $key);
}
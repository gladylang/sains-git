<?php
namespace I18N;

class Language {
  protected $lang;
  static $ENGLISH = 'EN';
  static $MALAY = 'MS';
  static $DEFAULT_LANGUAGE = 'EN';

  protected static $instance = null;
  
  public static function set(Language $instance) {
    static::$instance = $instance;
  }

  public static function get($key) {
    return static::$instance->getValue($key);
  }

  public static function getHtml($key) {
    return \Util\Text::escapeHtml(static::get($key));
  }

  public static function getJavascript($key) {
    return '"' . \Util\Text::escapeJS(static::get($key)) . '"';
  }

  public static function echoHtml($key) {
    echo \Util\Text::escapeHtml(static::get($key));
  }

  public static function echoJavascript($key) {
    echo '"',\Util\Text::escapeJS(static::get($key)),'"';
  }
  
  public function __construct($lang = null) {
    if ($lang == null) {
      $this->lang = static::$DEFAULT_LANGUAGE;
    } else {
      $this->lang = \strtoupper($lang);
    }
  }
  
  public function getValue($key) {
    $lang = $this->lang;
    $val;
    if (isset($this->$key) && isset($this->{$key}[$lang])) {
      $val = $this->{$key}[$lang];
    } else {
      $val = $this->getValueImpl($key);
      if (!isset($val)) {
        \Util\Logger::error("Language value does not exist for '$key'  Language '$lang'");
        if (isset($this->$key) && isset($this->{$key}[static::$DEFAULT_LANGUAGE])) {
          return $this->{$key}[static::$DEFAULT_LANGUAGE];
        }
        $val = '*' . $key  . ' - ' . $lang . '*';
      }
    }
    return $val;
  }

  public function getValueImpl($key) {
    return null;
  }
}
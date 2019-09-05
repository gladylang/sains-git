<?php
namespace Util;

class Text {
  /**
   * Escape special characters for HTML
   **/
  public static function escapeHtml($value) {
    return htmlspecialchars($value,ENT_QUOTES);
  }

  //@Deprecated
  public static function escapeJavascript($value) {
    return json_encode($value);
  }

  /**
   * Escape special characters for Javascript
   **/
  public static function escapeJS($value) {
    if (!isset($value) || empty($value)) { 
      return '';
    }
    $v2 = '';
    $arr = preg_split('//u',$value, -1, PREG_SPLIT_NO_EMPTY);
    $count = count($arr);
    $c = 0;
    for ($i=0;$i < $count;$i++) {
      $b = $c;
      $c = $arr[$i];

      switch ($c) {
        case '\'':
        case '"':
        case '\\':
          $v2 .= "\\$c";
          break;
        case '/':
          if ($b == '<') {
            $v2 .= '\\';
          }
          $v2 .= $c;
          break;
        case "\b":
          $v2 .= '\\b';
          break;
        case "\t":
          $v2 .= '\\t';
          break;
        case "\n":
          $v2 .= '\\n';
          break;
        case "\f":
          $v2 .= '\\f';
          break;
        case "\r":
          $v2 .= '\\r';
          break;
        default:
         $o = static::_uniord($c);
          if ($o < 32 || ($o >= 128 && $o < 160)
            || ($o >= 8192 && $o < 8448)) {
            $hex = dechex($o);
            $v2 .= '\u' . substr('0000',strlen($hex)) . $hex;
          } else {
            $v2 .= $c;
          }
        }
      }
      return $v2;
  }

  private static function _uniord($c) {
    if (ord($c{0}) >=0 && ord($c{0}) <= 127)
        return ord($c{0});
    if (ord($c{0}) >= 192 && ord($c{0}) <= 223)
        return (ord($c{0})-192)*64 + (ord($c{1})-128);
    if (ord($c{0}) >= 224 && ord($c{0}) <= 239)
        return (ord($c{0})-224)*4096 + (ord($c{1})-128)*64 + (ord($c{2})-128);
    if (ord($c{0}) >= 240 && ord($c{0}) <= 247)
        return (ord($c{0})-240)*262144 + (ord($c{1})-128)*4096 + (ord($c{2})-128)*64 + (ord($c{3})-128);
    if (ord($c{0}) >= 248 && ord($c{0}) <= 251)
        return (ord($c{0})-248)*16777216 + (ord($c{1})-128)*262144 + (ord($c{2})-128)*4096 + (ord($c{3})-128)*64 + (ord($c{4})-128);
    if (ord($c{0}) >= 252 && ord($c{0}) <= 253)
        return (ord($c{0})-252)*1073741824 + (ord($c{1})-128)*16777216 + (ord($c{2})-128)*262144 + (ord($c{3})-128)*4096 + (ord($c{4})-128)*64 + (ord($c{5})-128);
    if (ord($c{0}) >= 254 && ord($c{0}) <= 255)    //  error
        return FALSE;
    return 0;
  }   //  function _uniord()

  /**
   * URL encode a string
   **/
  public static function escapeUrl($value) {
    return urlencode($value);
  }

  /**
   * Escape special characters for HTML
   **/
  public static function h($value) {
    return static::escapeHtml($value);
  }

  /**
   * Escape special characters for Javascript
   **/
  public static function j($value) {
    return static::escapeJS($value);
  }
  
  /**
   * Escape special characters for URLs
   **/
  public static function u($value) {
    return static::escapeUrl($value);
  }

  /**
   * Escape Javascript, and escape HTML
   * Useful for embedding Javascript values in HTML attributes
   * E.G. <label onClick="<?php echo Text::hj($value); ?>">
   **/ 
  public static function hj($value) {
    return static::escapeHtml(static::escapeJS($value));
  }
}

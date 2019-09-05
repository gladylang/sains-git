<?php
// Class loader search sequence, LIB_PATH, APP_PATH/inc, EXTRA_LIB_PATH[0]/inc...EXTRA_LIB_PATH[1]/inc
// Where EXTRA_LIB_PATH paths are separated by semi colon ;
function framework_autoload($classname) {
  $classname = str_replace('\\', '/', $classname);
  $libFile = LIB_PATH . $classname . '.php';
  $appFile = APP_PATH . 'inc/' . $classname . '.php';

  if (is_file($appFile)) {
    include_once($appFile);
  } else if (is_file($libFile)){
    include_once($libFile);
  } else {
    if (defined('COMMON_PATH')) {
      $commonFile = COMMON_PATH . 'inc/' . $classname . '.php';
      if (is_file($commonFile)) {
        include_once($commonFile);
        return;
      }
    } 
    if (defined('EXTRA_LIB_PATH')) {
      $v = explode(';', EXTRA_LIB_PATH);
      foreach ($v as $path) {
        $extraFile = $path . 'inc/' . $classname . '.php';
        if (is_file($extraFile)) {
          include_once($extraFile);
          return;
        }
      }
    }
    return false;
  }
}

spl_autoload_register('framework_autoload');
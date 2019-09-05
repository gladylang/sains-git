<?php
namespace Data;

class ParamBuilder {
  // Query Fragment built
  public $query;
  // Parameters for binding
  public $params;
  // Parameter types
  public $types;
 
  protected $first;
 
  protected $separator;
 
  protected $ignoreIfNull;

  // $separator - Separator between parameters
  // $first - if first = true, then first addParam will not append separator
  // $ignoreIfNull - Ignore addParam is value is null?
  public function __construct($separator = ', ', $first = true, $ignoreIfNull = false) {
    $this->query = '';
    $this->params = array();
    $this->types = array();
    $this->first = $first;
    $this->separator = $separator;
    $this->ignoreIfNull = $ignoreIfNull;
  }

  public function setSeparator($separator) {
    $this->separator = $separator;
  }

  public function getSeparator() {
    return $this->separator;
  }

  // Add a parameter to list. 
  public function addParam($columnName, $value, $type = null) {
    $this->addParamFull("$columnName = :$columnName", ':' . $columnName, $value, $type);
  }

  // Add a Query part and parameter to list.
  // E.G. $pb->addParamFull('id > :idgt',':idgt',5);
  public function addParamFull($queryFragment, $variable, $value, $type = null) {
    if ($this->ignoreIfNull 
        && (!isset($value) || (is_array($value) && count($value) == 0))) {
      return;
    }
    if ($this->first) {
      $this->first = false;
    } else {
      $this->query .= $this->separator;
    }
    $this->query .= $queryFragment;
    if (is_array($variable)) {
      if (!is_array($value) || count($value) != count($variable)) {
        \Util\Logger::error("Invalid parameters for ParamBuilder->addParamFull.\nvariable=" . print_r($variable,true) . "\nvalue=" . print_r($value,true));
        return;
      }
      foreach ($variable as $i => $var) {
        $this->params[$var] = $value[$i];
        if (isset($type)) {
          if (is_array($type)) {
            if (isset($type[$i])) {
              $this->types[$var] = $type[$i];
            }
          } else {
            $this->types[$var] = $type;
          }
        }
      }
    } else {
      $this->params[$variable] = $value;
      if (isset($type)) {
        $this->types[$variable] = $type;
      }
    }
  }

  // Add a array to list
  // E.G. addParamArray('test',array('a','b','c'));
  // Output: query: (test = :test_0 OR test = :test_1 OR test = :test_2), params = array('test_0' => 'a', 'test_1' => 'b', 'test_2' => 'c')
  public function addParamArray($columnName, $valueArray, $type = null, $operator = '=',$separator = ' OR ', $prefix = '(', $postfix = ')') {
    if (!isset($valueArray) || !is_array($valueArray) || count($valueArray) < 1) {
      return;
    }

    $q = $prefix;
    $first = true;
    $vars = array();
    foreach ($valueArray as $i => $value) {
      if (!$first) {
        $q .= ' ' . $separator . ' ';
      } else {
        $first = false;
      }
      $variable = ':' . $columnName . '_' . $i;
      $q .= "$columnName $operator $variable";
      $vars[$i] = $variable;
    }
    if (isset($postfix)) {
      $q .= $postfix;
    }
    $this->addParamFull($q, $vars, $valueArray, $type);
  }
}

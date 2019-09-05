<?php
namespace Data;

class InsertParamBuilder {
  // ColumnName String for insert
  public $columnNames;
  // ColumnValues for insert
  public $columnValues;
  // Parameters for binding
  public $params;
  // Parameter types
  public $types;
  
  protected $first;
  
  protected $separator;
  
  protected $databaseUtil;
  
  protected $ignoreIfNull;

  // $separator - Separator between parameters
  // $first - if first = true, then first addParam will not append separator
  // $ignoreIfNull - Ignore addParam is value is null?
  // $databaseUtil - Database util class for database connection.  Used to delimit column identifiers.
  public function __construct($separator = ', ', $first = true, $ignoreIfNull = false, $databaseUtil = null) {
    $this->columnNames = ''; //added by jacky
    $this->columnValues = '';
    $this->params = array();
    $this->types = array();
    $this->first = $first;
    $this->separator = $separator;
    $this->ignoreIfNull = $ignoreIfNull;
    $this->databaseUtil = $databaseUtil;
  }

  // Add a parameter to list. 
  public function addParam($columnName, $value, $type = null) {
    $this->addParamFull($columnName, ':' . $columnName, $value, $type);
  }
  
  // Add a Query part and parameter to list.
  // E.G. $pb->addParamFull('id', ':id', 5);
  public function addParamFull($columnName, $variable, $value, $type = null) {
    if ($this->ignoreIfNull && !isset($value)) {
      return;
    }
    if ($this->first) {
      $this->first = false;
    } else {
      $this->columnNames .= $this->separator;
      $this->columnValues .= $this->separator;
    }
    if ($this->databaseUtil != null) {
      $this->columnNames .= $this->databaseUtil->delimitIdentifier($columnName);
    } else {
      $this->columnNames .= $columnName;
    }
    $this->columnValues .= $variable;
    $this->params[$variable] = $value;
    if (isset($type)) {
      $this->types[$variable] = $type;
    }
  }
}

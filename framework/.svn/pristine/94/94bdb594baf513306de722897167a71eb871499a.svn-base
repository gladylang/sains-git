<?php
namespace Mail;

// Load attachment from file
class FileAttachment extends Attachment {
  public $sourceFileName = null;
  
  public function __construct($sourceFileName) {
    $this->sourceFileName = $sourceFileName;
  }
  
  public function toString() {
    return 'File ' . realpath($this->sourceFileName);
  }
}
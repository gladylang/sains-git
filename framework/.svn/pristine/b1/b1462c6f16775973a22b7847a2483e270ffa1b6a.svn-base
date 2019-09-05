<?php
namespace Mail;

// Load attachment from binary data in memory
class BlobAttachment extends Attachment {
  public $filename = null;
  public $mimeType = null;
  public $content = null;
  
  public function __construct($content, $filename, $mimeType) {
    $this->content = $content;
    $this->filename = $filename;
    $this->mimeType = $mimeType;
  }
  
  public function toString() {
    return 'Blob ' . strlen($this->content) .' bytes';
  }
}
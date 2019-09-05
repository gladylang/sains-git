<?php
namespace Mail;
use \Util\Logger;

class NullMailer extends Mailer {
  public function __construct($config = null) {
    parent::__construct($config);
  }
  
  public function send($sender, $recipient, $subject, $bodyText, $bodyHtml = null, array $attachments = null){
    $attachmentMessage = '';
    if (isset($attachments)) {
      foreach ($attachments as $attachment) {
        if ($attachment instanceof Attachment) {
          $sa = null;
          if (empty($attachmentMessage)) {
            $attachmentMessage = "\r\nAttachments: " . count($attachments) . ' : ';
          } else {
            $attachmentMessage .= ', ';
          }
          $attachmentMessage .= $attachment->toString();
        } else {
          Logger::error('Invalid attachment object: ' . print_r($attachment, true));
        }
      }
    }
    if (Logger::isInfo()) {
      Logger::info("NullMailer Send Mail:\r\n" . sprintf("Sender: %s\r\nRecipient: %s\r\nSubject: %s\r\nBody Text: %s\r\nBody Html: %s%s",print_r($sender,true), print_r($recipient,true), $subject, $bodyText, $bodyHtml, $attachmentMessage));
    }
    return true;
  }
}
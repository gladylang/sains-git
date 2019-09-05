<?php
namespace Mail;
use \Util\Logger;

require_once (__DIR__ . '/../ext/swift/swift_required.php');

class SwiftMailer extends Mailer {
  protected $transport = null;
  protected $mailer = null;
  protected $logger = null;
  public function __construct($config = null) {
    parent::__construct($config);
    if (!isset($this->config['transport']) || (strcasecmp($this->config['transport'],'smtp') == 0)) {
      $this->createSmtpTransport();
    } else if (strcasecmp($this->config['transport'],'sendmail') == 0) {
      $this->createSendmailTransport();
    } else {
      throw new MailException("Mailer invalid transport: " . $this->config['transport']);
    }
    $this->mailer = \Swift_Mailer::newInstance($this->transport);
    if (Logger::isLevel(Logger::TRACE) && isset($this->config['logging']) && $this->config['logging'] === true) {
      $this->logger = new \Swift_Plugins_Loggers_ArrayLogger();
      $this->mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($this->logger));
    }
  }

  protected function createSmtpTransport() {
    $encryption = null;
    if (isset($this->config['encryption'])) {
      $this->config['encryption'] = trim(strtolower($this->config['encryption']));
      if ($this->config['encryption'] === 1 || $this->config['encryption'] === '1' || $this->config['encryption'] === true) {
        $this->config['encryption'] = 'ssl';
      }
      if ($this->config['encryption'] == 'ssl' || $this->config['encryption'] == 'tls') {
        $encryption = $this->config['encryption'];
      } else if (!empty($this->config['encryption']) && $this->config['encryption'] !== false && $this->config['encryption'] !== 0) {
        Logger::error('Unknown mailer encryption type: ' . $this->config['encryption']);
      }
    }

    if (!isset($this->config['port'])) {
      if (isset($encryption)) {
        $this->config['port'] = 465;
      } else {
        $this->config['port'] = 25;
      }
    }
    $this->transport = \Swift_SmtpTransport::newInstance($this->config['server'], $this->config['port']);
    if (isset($encryption)) {
      $this->transport->setEncryption($encryption);
    }
    if (!empty($this->config['username'])) {
      $this->transport->setUsername($this->config['username']);
    }
    if (!empty($this->config['password'])) {
      $this->transport->setPassword($this->config['password']);
    }
  }

  protected function createSendmailTransport() {
    if (isset($this->config['command'])) {
      $this->transport = \Swift_SendmailTransport::newInstance($this->config['command']);
    } else {
      $this->transport = \Swift_SendmailTransport::newInstance();
    }
  }

  public function send($sender, $recipient, $subject, $bodyText, $bodyHtml = null, array $attachments = null) {
   $success = false;
   $attachmentMessage = '';
    try {
      $message = \Swift_Message::newInstance($subject)
        ->setFrom($sender)
        ->setTo($recipient)
        ->setBody($bodyText);
      if (isset($bodyHtml)) {
        $message->addPart($bodyHtml, 'text/html');
      }
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
            if ($attachment instanceof BlobAttachment) {
              $sa = \Swift_Attachment::newInstance($attachment->content, $attachment->filename, $attachment->mimeType);
            } else if ($attachment instanceof FileAttachment) {
              $sa = \Swift_Attachment::fromPath($attachment->sourceFileName);
            } else {
              throw new Exception("Unhandled Attachment type. " . $attachment->toString());
            }
            if (isset($sa)) {
              $message->attach($sa);
            }
          } else {
            Logger::error('Invalid attachment object: ' . print_r($attachment, true));
          }
        }
      }
      $result = $this->mailer->send($message);
      if ($result > 0) {
        $success = true;
      }
    } catch (\Swift_Exception $e) {
      Logger::error('Send Mail: ' . print_r($e, true));
      $result = 0;
    }
    if (Logger::isLevel(Logger::TRACE)) {
      Logger::trace("SwiftMailer Send Mail:\r\n" . sprintf("Result: %s\r\nSender: %s\r\nRecipient: %s\r\nSubject: %s\r\nBody Text: %s\r\nBody Html: %s%s",$success?'success':('failed (' . $result . ')'),print_r($sender,true), print_r($recipient,true), $subject, $bodyText, $bodyHtml, $attachmentMessage));
      if (isset($this->logger)) {
        Logger::trace("SwiftMailer log:\r\n" . $this->logger->dump());
      }
    }
    return $success;
  }
  
  public function sendSwiftMessage(\Swift_Message $message) {
   $success = false;
    try {
      $result = $this->mailer->send($message);
      if ($result == 1) {
        $success = true;
      }
    } catch (\Swift_Exception $e) {
      Logger::error('Send Mail: ' . print_r($e, true));
      $result = 0;
    }
    if (Logger::isLevel(Logger::TRACE)) {
      Logger::trace("SwiftMailer Send Mail:\r\n" . sprintf("Result: %s\r\n",$success?'success':('failed (' . $result . ')')));
    }
    return $success;
  }
}
<?php
namespace Mail;

abstract class Mailer {
  protected $config = array();
  public function __construct($config) {
    if (!is_null($config)) {
      if (is_array($config)) {
        $this->config = $config;
      } else {
        \Util\Logger::debug('Mailer invalid configuration. ' . print_r($config));
      }
    }
  }

  public abstract function send($sender, $recipient, $subject, $bodyText, $bodyHtml = null, array $attachments = null);
}
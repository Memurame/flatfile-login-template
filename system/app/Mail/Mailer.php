<?php

namespace App\Mail;

use PHPMailer\PHPMailer\Exception;

class Mailer
{
  protected $view;

  protected $mailer;

  protected $path;

  protected $sys;

  public function __construct($container, $mailer, $settings, $path)
  {
    $this->view = $container->view;
    $this->mailer = $mailer;
    $this->sys = $settings;
    $this->path = $path;
  }

  public function send($template, $data, $callback)
  {
    $message = new Message($this->mailer);

    $message->body($this->view->fetch($template, $data));

    call_user_func($callback, $message);
    try{
      $this->mailer->send();
      return true;
    } catch (Exception $e) {
      return false;
    }

  }

  public function addToQueue($to, $type, $content = false){
    $filename = time() . '-' . uniqid();

    $reset_db = new \Filebase\Database([
      'dir' => $this->path,
      'format' => \Filebase\Format\Yaml::class,
    ]);

    $reset = $reset_db->get($filename);
    $reset->to = $to;
    $reset->type = $type;
    $reset->from_name = $this->sys['sys']['mail']['from_name'];
    $reset->from_mail = $this->sys['sys']['mail']['from'];

    if(is_array($content)){
      $reset->array = $content;
    } else {
      $reset->text = $content;
    }

    $reset->save();

    return $filename;
  }
}

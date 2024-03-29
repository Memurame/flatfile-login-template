<?php

require_once realpath(__DIR__ . '../../') . '/define.php';

require_once realpath(__DIR__ . '../../../') . '/vendor/autoload.php';

$app = new \Slim\App(['settings' => require PATH_ROOT . DIR_SYSTEM . 'settings.php']);
$container = $app->getContainer();
require PATH_ROOT . DIR_SYSTEM . 'container.php';

$container->get('config')['backgroundTask'] = true;

$queue_db = new \Filebase\Database([
  'dir' => PATH_ROOT . DIR_TEMP . DIR_TEMP_QUEUE,
  'format' => \Filebase\Format\Yaml::class,
]);

$filelist = $queue_db->findAll();

foreach($filelist as $file){
  if($file->type == 'welcome'){
    $container['mailer']->send('mails/welcome.twig', function($message) use ($file){
      $message->to($file->to);
      $message->subject('Account erstellt!');
      $message->from($file->from_mail);
      $message->fromName($file->from_name);
    });
  }
  elseif($file->type == 'forgot'){
    $container['mailer']->send('mails/forgot.twig', ['url' => $file->text] , function($message) use ($file){
      $message->to($file->to);
      $message->subject('Password vergessen!');
      $message->from($file->from_mail);
      $message->fromName($file->from_name);
    });
  }
  elseif($file->type == 'password'){
    $container['mailer']->send('mails/password.twig', [] , function($message) use ($file){
      $message->to($file->to);
      $message->subject('Password geändert!');
      $message->from($file->from_mail);
      $message->fromName($file->from_name);
    });
  }
  elseif($file->type == 'password_error'){
    $container['mailer']->send('mails/password_error.twig', ['data' => $file->array] , function($message) use ($file){
      $message->to($file->to);
      $message->subject('Fehlerhaftes Login!');
      $message->from($file->from_mail);
      $message->fromName($file->from_name);
    });
  }
  elseif($file->type == 'brute'){
    $container['mailer']->send('mails/brute.twig', ['url' => $file->text] , function($message) use ($file){
      $message->to($file->to);
      $message->subject('Account gesperrt!');
      $message->from($file->from_mail);
      $message->fromName($file->from_name);
    });
  }

  $file->delete();

}

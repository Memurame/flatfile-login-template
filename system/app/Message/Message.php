<?php

namespace App\Message;

class Message
{
  protected $fromPrevious = [];

  protected $storage;

  protected $storageKey = 'MeMeMu';

  public function __construct($container){
    $this->container = $container;

    if (!isset($_SESSION)) {
      throw new \RuntimeException('Session not found.');
    }
    $this->storage = &$_SESSION;

    if (isset($this->storage[$this->storageKey]) && is_array($this->storage[$this->storageKey])) {
      $this->fromPrevious = $this->storage[$this->storageKey];
    }
    $this->storage[$this->storageKey] = [];
  }

  public function addFlash($type, $message, $timeout = 3){
    $this->storage[$this->storageKey]['flash'][] = [
      'type' => $type,
      'text' => $message,
      'timeout' => $timeout
    ];
  }

  public function addInline($type, $message){
    $this->storage[$this->storageKey]['inline'][] = [
      'type' => $type,
      'text' => $message
    ];
  }
  public function write($type, $message){
    $this->renderMessage([
      'type' => $type,
      'text' => $message
    ]);
  }

  public function getMessages($type){
    return (isset($this->fromPrevious[$type])) ? $this->fromPrevious[$type] : false;
  }

  public function renderMessage(array $message_array){
    return $this->container->view->render($this->container['response'], 'message/site.twig', ['text' => $message_array]);
  }
}

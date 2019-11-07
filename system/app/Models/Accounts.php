<?php

namespace App\Models;

Class Accounts{

  protected $filebase;


  public function __construct()
  {
    $this->filebase = new \Filebase\Database([
      'dir' => PATH_ACCOUNTS,
      'format' => \Filebase\Format\Yaml::class,
    ]);
  }

  public function __call($method, $arguments)
  {
    return call_user_func_array([$this->filebase,$method],$arguments);
  }

}

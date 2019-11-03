<?php

namespace App\Models;

use \Filebase\Database;
use \Filebase\Format;

Class Accounts extends Database{

  protected $filebase;


  public function __construct()
  {
    $this->filebase = new Database([
      'dir' => PATH_ACCOUNTS,
      'format' => Format\Yaml::class,
    ]);
  }

  public function __call($method, $arguments)
  {
    return $this->filebase->__call($method, $arguments);
  }

}

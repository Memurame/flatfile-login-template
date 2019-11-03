<?php

namespace App\Models;

use \Filebase\Database;

Class Token extends Database{

  protected $filebase;


  public function __construct()
  {
    $this->filebase = new Database([
      'dir' => PATH_ROOT . DIR_TEMP . DIR_TEMP_TOKEN,
      'format' => \Filebase\Format\Yaml::class,
    ]);
  }

  public function __call($method, $arguments)
  {
    return $this->filebase->__call($method, $arguments);
  }

}

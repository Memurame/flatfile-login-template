<?php

namespace App\Translate;

use RuntimeException;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class Translate
{
  /**
   * @var string []
   */
  private $allowedLocales = [];

  /**
   * @var string
   */
  private $defaultLocale;

  /**
   * @var string
   */
  private $currentLocale;

  /**
   * @var string
   */
  private $path;

  /**
   * @var
   */
  public static $translator;

  /**
   * @var bool
   */
  private $fromGet = false;


  public function __construct($path, $default = 'de'){
    $this->path = $path;

    $this->getAllowedLocales();
    $this->setDefault($default);
    $this->getCurrentLocale();

    if(!file_exists($this->getFilename(true))){
      throw new RuntimeException('No translate file found.');
    }

    setcookie('language', $this->currentLocale, time()+60*60*24*180, '/', $_SERVER['HTTP_HOST']);

    self::$translator = new Translator($this->currentLocale);

    self::$translator->addLoader('yaml', new YamlFileLoader());
    self::$translator->addResource('yaml', $this->path . '/' . $this->currentLocale . '.yaml' , $this->currentLocale);

  }

  private function setDefault($locale){
    $this->defaultLocale = $locale;
  }

  private function getFilename($absolute = false){
    $filename =  $this->currentLocale . '.yaml';
    return ($absolute)? $this->path . $filename : $filename;
  }

  public function fromGET(){
    return $this->fromGet;
  }

  public static function translate($text){
    return self::$translator->trans($text);
  }

  private function validateLocale($locale){
    return (in_array($locale, $this->allowedLocales)) ? true : false;
  }

  private function setCurentLocale($locale){
    if($this->validateLocale($locale)){
      $this->currentLocale = $locale;
      return $locale;
    }
    return false;
  }

  public function getLocale(){
    return $this->currentLocale;
  }

  private function getCurrentLocale()
  {
    if (isset($_GET['locale'])) {
      $this->fromGet = true;
      if ($this->setCurentLocale($_GET['locale'])) return $this->currentLocale;
    }

    if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], $this->allowedLocales)) {
      if ($this->setCurentLocale($_COOKIE['language'])) return $this->currentLocale;
    }

    $browserLang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $browserLang = substr($browserLang[0], 0, 2);
    if ($this->setCurentLocale($browserLang)) return $this->currentLocale;

    $this->setCurentLocale($this->defaultLocale);
  }

  private function checkDir(){
    if(!is_dir($this->path)){
      $this->createDir();
    }
  }

  private function createDir(){
    mkdir($this->path, 0755, true);
  }


  private function getAllowedLocales(){
    $this->checkDir();
    $result = array();
    $cdir = scandir($this->path);
    foreach ($cdir as $key => $value)
    {
      if (!in_array($value,array(".","..",".gitkeep")))
      {
        if (!is_dir($this->path . DIRECTORY_SEPARATOR . $value))
        {
          $locale = substr($value, 0, -5);
          $result[] = $locale;
        }
      }
    }

    $this->allowedLocales = $result;
    return $result;
  }
}

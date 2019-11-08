<?php

namespace App\Translate;

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
  private $defaultLocale = 'en';

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
  private $translator;

  /**
   * @var bool
   */
  private $fromGet = false;


  public function __construct($path){
    $this->path = $path;

    $this->getAllowedLocales();
    $this->currentLocale = $this->getCurrentLocale();

    setcookie('language', $this->currentLocale, time()+60*60*24*180, '/', $_SERVER['HTTP_HOST']);

    $this->translator = new Translator($this->currentLocale);

    $this->translator->addLoader('yaml', new YamlFileLoader());
    $this->translator->addResource('yaml', $this->path . '/' . $this->currentLocale . '.yaml' , $this->currentLocale);

  }

  public function fromGET(){
    return $this->fromGet;
  }

  public function getTranslation(){

    $return = [
      'locale' => $this->currentLocale
    ];

    return $return;
  }

  private function getCurrentLocale(){
    $lng = $this->defaultLocale;
    if(isset($_GET['locale'])) {
      $this->fromGet = true;
      $lng = $_GET['locale'];
    }

    if(in_array($lng, $this->allowedLocales)){
      return $lng;
    }

    if(isset($_COOKIE['language']) && in_array($_COOKIE['language'], $this->allowedLocales)){
      return $_COOKIE['language'];
    }

    $browserLang = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $browserLang = substr($browserLang[0], 0, 2);
    if (in_array($browserLang, $this->allowedLocales)) {
      return $browserLang;
    }

    return $lng;
  }

  private function getAllowedLocales(){
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

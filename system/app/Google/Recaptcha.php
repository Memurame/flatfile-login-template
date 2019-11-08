<?php

namespace App\Google;

use RuntimeException;

class Recaptcha
{

  /**
   * reCAPTCHA private key
   *
   * @var string
   */
  private static $private;

  /**
   * reCAPTCHA public key
   *
   * @var string
   */
  private static $public;

  /**
   * reCAPTCHA verify url
   *
   * @var string
   */
  private static $url = 'https://www.google.com/recaptcha/api/siteverify';

  /**
   * reCAPTCHA version, 2 or 3
   *
   * @var int
   */
  private static $version = 2;

  /**
   * Request from reCAPTCHA form input
   *
   * @var string
   */
  private static $request;

  /**
   * IP address from the client
   *
   * @var string
   */
  private static $ip;

  /**
   * Set the public and private key of reCAPTCHA
   *
   * @param $private
   * @param $public
   */
  public static function setKeys($private, $public){
    self::$private = $private;
    self::$public = $public;
  }

  /**
   * Set the reCAPTCHA version
   *
   * @param $version
   */
  public static function setVersion($version){
    if($version != 2 and $version != 3){
      throw new RuntimeException('Only version 2 and 3 available.');
    }
    self::$version = $version;
  }

  public static function failed(){
    return ((new self)->validate() == false) ? true : false;
  }

  /**
   * Validate the request from the form with the correct algorithmus
   *
   * @return bool
   */
  private function validate(){

    if((new self)->checkKeys()){
      throw new RuntimeException('No private or public key.');
    }

    self::$ip = $_SERVER['REMOTE_ADDR'];
    if(self::$version == 2){
      if(!isset($_POST['g-recaptcha-response'])){
        throw new RuntimeException('g-recaptcha-response not exists');
      }
      self::$request = $_POST['g-recaptcha-response'];
      return (new self)->v2();
    } elseif(self::$version == 3){
      if(!isset($_POST['recaptcha_response'])){
        throw new RuntimeException('recaptcha_response not exists');
      }
      self::$request = $_POST['recaptcha_response'];
      return (new self)->v3();
    } else{
      return false;
    }
  }

  /**
   * Validate version 2 reCAPTCHA
   *
   * @return bool
   */
  private function v2(){
    $json = file_get_contents(self::$url .'?secret='.self::$private.'&response='.self::$request.'&remoteip='.self::$ip);
    $recaptcha = json_decode($json,true);
    if(intval($recaptcha['success']) !== 1) {
      return false;
    }
    return true;
  }

  /**
   * Validate version 3 reCAPTCHA
   *
   * @return bool
   */
  private function v3(){
    $json = file_get_contents(self::$url . '?secret=' . self::$private . '&response=' . self::$request);
    $recaptcha = json_decode($json);
    if ($recaptcha['success'] && $recaptcha['score'] < 0.5) {
      return false;
    }
    return true;
  }

  /**
   * Check if the private and public key exists
   *
   * @return bool
   */
  private function checkKeys(){
    return (empty(self::$private) || empty(self::$public));
  }
}

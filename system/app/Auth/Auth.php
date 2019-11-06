<?php

namespace App\Auth;

use App\Controllers\Controller;

class Auth
{

  private $user;

  private $user_dir;

  private $user_db;

  private $reset_dir;

  private $reset_db;

  private $token;

  private $token_dir;

  private $token_db;

  private $cookie_name = "flatlogin";

  private $cookie_lifetime = 3600;

  private $brute_tries = 5;

  /**
   * @param bool $userid
   * @return bool
   */
  public function user($userid = false){
    if($userid){
      $user = ($this->user_db->has($userid)) ? $this->user_db->get($userid) : false;
    } else {
      $user = $this->user_db->get($this->token->userid);
    }

    return (!$user) ? false : $user;
  }

  /**
   * @param $dir
   * @throws \Filebase\Filesystem\FilesystemException
   */
  public function setTokenDir($dir){
    $this->token_dir = $dir;
    $this->token_db = new \Filebase\Database([
      'dir' => $this->token_dir,
      'format' => \Filebase\Format\Yaml::class,
    ]);
  }

  /**
   * @param $dir
   * @throws \Filebase\Filesystem\FilesystemException
   */
  public function setResetDir($dir){
    $this->reset_dir = $dir;
    $this->reset_db = new \Filebase\Database([
      'dir' => $this->reset_dir,
      'format' => \Filebase\Format\Yaml::class,
    ]);
  }

  /**
   * @param $dir
   * @throws \Filebase\Filesystem\FilesystemException
   */
  public function setUserDir($dir){
    $this->user_dir = $dir;
    $this->user_db = new \Filebase\Database([
      'dir' => $this->user_dir,
      'format' => \Filebase\Format\Yaml::class,
    ]);
  }

  /**
   * @return mixed
   */
  public function getUserDB(){
    return $this->user_db;
  }

  /**
   * @return mixed
   */
  public function getResetDB(){
    return $this->reset_db;
  }

  public function updateAuthToken(){
    $this->token->token = $this->createToken(64);
    $this->token->save();

    setcookie($this->cookie_name . '_token', $this->token->token, time()+86400*365, '/', $_SERVER['HTTP_HOST']);

  }

  /**
   * @return bool
   */
  public function getAuthToken(){
    if(!isset($_COOKIE[$this->cookie_name . '_identifier']) || !isset($_COOKIE[$this->cookie_name . '_token'])) return false;

    if($this->token_db->has($_COOKIE[$this->cookie_name . '_identifier'])){
      $token = $this->token_db->get($_COOKIE[$this->cookie_name . '_identifier']);

      if($token->token == $_COOKIE[$this->cookie_name . '_token']){
        return $token;
      }
    }
    return false;
  }

  /**
   * @param $userid
   * @throws \Exception
   */
  public function setAuthToken($userid){

    $created_token = $this->createToken(15);

    $token = $this->token_db->get($created_token);
    $token->token = $this->createToken(64);
    $token->identifier = $created_token;
    $token->userid = $userid;
    $token->save();

    setcookie($this->cookie_name . '_identifier', $token->identifier, time()+86400*365, '/', $_SERVER['HTTP_HOST']);
    setcookie($this->cookie_name . '_token', $token->token, time()+86400*365, '/', $_SERVER['HTTP_HOST']);
  }

  /**
   * @param $userid
   */
  public function deleteAllUserToken($userid)
  {
    $this->token_db->where(['userid' => $userid])->delete();
    $this->logout();
  }

  /**
   * @param $user_id
   * @return bool
   */
  public function checkBrute($user_id){
    $apc_key = "{$_SERVER['SERVER_NAME']}~user:{$user_id}";
    $tries = (int)apcu_fetch($apc_key);
    if ($tries >= $this->brute_tries) {
      return true;
    }
    return false;
  }

  /**
   * @param $username
   * @param $password
   * @return mixed
   * @throws \Exception
   */
  public function login($username, $password){

    $user = $this->user_db->where(['username' => $username])->first();
    if (!$user && !isset($error)) {
      $error = 'NOUSER';
    }

    if($this->checkBrute($user['userid']) && !isset($error)){
      $error = 'BRUTE';
      die($error);
    }

    $apc_key = "{$_SERVER['SERVER_NAME']}~user:{$user['userid']}";
    $apc_blocked_key = "{$_SERVER['SERVER_NAME']}~user-blocked:{$user['userid']}";

    if (!isset($error) && !$this->checkHash($password, $user['password_hash'])) {
      $blocked = (int)apcu_fetch($apc_blocked_key) + 1;
      $tries = (int)apcu_fetch($apc_key) + 1;
      apcu_store($apc_key, $tries, pow(2, $blocked)*60);  # store tries for 2^(x+1) minutes: 2, 4, 8, 16, ...
      apcu_store($apc_blocked_key, $blocked, 86400);  # store number of times blocked for 24 hours

      if($tries < $this->brute_tries){
        $error = 'PASSWORD';
      } elseif($tries == $this->brute_tries){
        $error = 'BLOCKED';
      }
    }

    if(!isset($error)){

      $this->setAuthToken($user['userid']);

      $return['success'] = true;
      $return['user'] = $user;

      apcu_delete($apc_key);
      apcu_delete($apc_blocked_key);

      return $return;
    }

    $return['success'] = false;
    $return['reason'] = ($error) ? $error : 'ERROR';

    return $return;
  }

  /**
   * @param $token
   * @return bool
   */
  public function allowedToResetPassword($token)
  {
    $reset = $this->reset_db->get($token);

    if(!$this->reset_db->has($token)) return false;

    $reset = $this->reset_db->get($token);

    return ($this->user($reset->userid)) ? $reset : false;

  }

  /**
   * User logout
   */
  public function logout()
  {
    if(isset($_COOKIE[$this->cookie_name . '_identifier']) && $this->token_db->has($_COOKIE[$this->cookie_name . '_identifier'])){
      $this->token_db->get($_COOKIE[$this->cookie_name . '_identifier'])->delete();
    }
    setcookie($this->cookie_name . '_identifier', '', time()-3600, '/', $_SERVER['HTTP_HOST']);
    setcookie($this->cookie_name . '_token', '', time()-3600, '/', $_SERVER['HTTP_HOST']);
  }

  /**
   * @param string $lvl
   * @return mixed
   */
  public function check($lvl = 'user'){

    $return['success'] = false;
    $return['reason'] = 'ERROR';

    $this->token = $this->getAuthToken();
    /*
     * Prüft ob ein Token vorhanden ist
     */
    if(!$this->token){
      $return['reason'] = 'NOTOKEN';
      $this->logout();
      return $return;
    }

    if(strtotime($this->token->updatedAt()) < time() - $this->cookie_lifetime){
      $return['reason'] = 'EXPIRED';
      $this->logout();
      return $return;
    } else {
      $this->updateAuthToken();
    }

    if($lvl == 'admin'){
      if($this->user->role == 'admin'){
        $return['success'] = true;
        return $return;
      } else {
        $return['reason'] = 'NOPERMISSION';
        $this->logout();
        return $return;
      }
    } else {
      $return['success'] = true;
      return $return;
    }
  }

  /**
   * @param $lenght
   * @return string
   */
  public function createPassword($lenght)
  {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789%&/()=?!-_";
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < $lenght; $i++) {
      $n = rand(0, $alphaLength);
      $pass[] = $alphabet[$n];
    }
    return implode($pass);
  }

  /**
   * @param int $bytes
   * @return string
   * @throws \Exception
   */
  public function createToken($bytes = 16){
    $hash = bin2hex(random_bytes($bytes));
    $time = md5(time());
    return $time . $hash;
  }

  /**
   * @param $value
   * @return bool|string
   */
  public function createHash ($value)
  {
    $hash = password_hash($value,PASSWORD_DEFAULT);
    return $hash;
  }

  /**
   * @param $value
   * @param $hash
   * @return bool
   */
  public function checkHash($value, $hash)
  {
    if(password_verify($value,$hash)){ return true; }
    else { return false; }
  }
}

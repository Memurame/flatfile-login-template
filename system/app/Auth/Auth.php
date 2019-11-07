<?php

namespace App\Auth;

use App\Controllers\Controller;

class Auth
{

  /**
   * @var object
   */
  private $user;

  /**
   * @var object
   */
  private $token;

  /**
   * @var string
   */
  private $cookie_name = "flatlogin";

  /**
   * @var int
   */
  private $cookie_lifetime = 3600;

  /**
   * @var int
   */
  private $brute_tries = 5;

  /**
   * @param bool $userid
   * @return bool
   */
  public function user($userid = false){
    $a = new \App\Models\Accounts();
    if($userid){
      $user = ($a->has($userid)) ? $a->get($userid) : false;
    } else {
      $user = $a->get($this->token->userid);
    }

    return (!$user) ? false : $user;
  }

  /**
   * @throws \Exception
   */
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

    $t = new \App\Models\Token();
    if($t->has($_COOKIE[$this->cookie_name . '_identifier'])){
      $token = $t->get($_COOKIE[$this->cookie_name . '_identifier']);

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

    $t = new \App\Models\Token();
    $token = $t->get($created_token);
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
    $t = new \App\Models\Token();
    $t->where(['userid' => $userid])->delete();
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

    $a = new \App\Models\Accounts();
    $user = $a->where(['username' => $username])->first();
    if (!$user && !isset($error)) {
      $error = 'NOUSER';
    }

    if($this->checkBrute($user['userid']) && !isset($error)){
      $error = 'BRUTE';
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
    $r = new \App\Models\Reset();
    $reset = $r->where('token', '=', $token)
      ->andWhere('type', '=', 'password')
      ->first();

    return ($this->user($reset['userid'])) ? $reset : false;

  }

  /**
   * User logout
   */
  public function logout()
  {
    $t = new \App\Models\Token();
    if(isset($_COOKIE[$this->cookie_name . '_identifier']) && $t->has($_COOKIE[$this->cookie_name . '_identifier'])){

      $t->get($_COOKIE[$this->cookie_name . '_identifier'])->delete();
    }
    setcookie($this->cookie_name . '_identifier', '', time()-3600, '/', $_SERVER['HTTP_HOST']);
    setcookie($this->cookie_name . '_token', '', time()-3600, '/', $_SERVER['HTTP_HOST']);
  }

  /**
   * @param string $lvl
   * @return mixed
   * @throws \Exception
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

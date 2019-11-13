<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use App\Google\Recaptcha;
use App\Translate\Translate;

class AuthController extends Controller
{
	public function getLogin($request, $response)
  {

    return $this->view->render($response, 'auth/login.twig');
  }

  public function postLogin($request, $response){

    $validation = $this->validator->validate($request, [
      'username' => v::noWhitespace()->notEmpty(),
      'password' => v::noWhitespace()->notEmpty()
    ]);

    if ($validation->failed()) {
      return $response->withRedirect($this->router->pathFor('auth.login'));
    }

    /*
     * reCaptcha prüfung (falls in den settings eingeschalten)
     */
    if($this->config['sys']['secure']['captcha']['enabled']) {
      Recaptcha::setKeys($this->config['sys']['secure']['captcha']['key']['private'], $this->config['sys']['secure']['captcha']['key']['public']);
      Recaptcha::setVersion($this->config['sys']['secure']['captcha']['version']);

      if (Recaptcha::failed()) {
        $this->message->addInline('danger', Translate::translate('login.msg.captcha'));
        return $response->withRedirect($this->router->pathFor('auth.login'));
      }
    }

    $auth = $this->auth->login(
      $request->getParam('username'),
      $request->getParam('password')
    );

    if (! $auth['success']) {
      $u = new \App\Models\Accounts();
      $user = $u->where(['username' => $request->getParam('username')])->first();
      if($auth['reason'] == 'PASSWORD'){
        $this->mailer->addToQueue($user['email'], 'password_error', ['ip' => $_SERVER['REMOTE_ADDR']]);
      } elseif($auth['reason'] == 'BLOCKED'){
        $token = $this->auth->createToken(15);

        $r = new \App\Models\Reset();
        $reset = $r->get($token);
        $reset->token = $token;
        $reset->userid = $user['userid'];
        $reset->type = 'brute';
        $reset->save();

        $this->mailer->addToQueue($user['email'], 'brute', $this->config['url']['host'] . $this->router->pathFor('auth.brute', ['token' => $token]));
      }

      $this->message->addInline('danger', Translate::translate('login.msg.loginerror'));
      return $response->withRedirect($this->router->pathFor('auth.login'));
    }

    return $response->withRedirect($this->router->pathFor('app.index'));
  }

  public function getLogout($request, $response)
  {
    $this->auth->logout();
    return $response->withRedirect($this->container->router->pathFor('auth.login'));
  }

  public function getBruteReset($request, $response, $args){
    $r = new \App\Models\Reset();
    $reset = $r->where('token', '=', $args['token'])
      ->andWhere('type', '=', 'brute')
      ->first();

    if($reset){
      $apc_key = "{$_SERVER['SERVER_NAME']}~user:{$reset['userid']}";
      $apc_blocked_key = "{$_SERVER['SERVER_NAME']}~user-blocked:{$reset['userid']}";
      apcu_delete($apc_key);
      apcu_delete($apc_blocked_key);

      $r = new \App\Models\Reset();
      $r->where(['token' => $reset['token'], 'type' => 'brute'])->delete();

      $this->message->addInline('success', Translate::translate('login.msg.reactivated'));
    }

    return $response->withRedirect($this->container->router->pathFor('auth.login'));
  }

  public function getForgot($request, $response)
  {
    return $this->view->render($response, 'auth/forgot.twig');
  }

  public function postForgot($request, $response){

    $validation = $this->validator->validate($request, [
      'username' => v::noWhitespace()->notEmpty()
    ]);

    if ($validation->failed()) {
      return $response->withRedirect($this->router->pathFor('auth.forgot'));
    }
    if($this->config['sys']['secure']['captcha']['enabled']) {
      Recaptcha::setKeys($this->config['sys']['secure']['captcha']['key']['private'], $this->config['sys']['secure']['captcha']['key']['public']);
      Recaptcha::setVersion($this->config['sys']['secure']['captcha']['version']);

      if (Recaptcha::failed()) {

        $this->message->addInline('danger', Translate::translate('login.msg.captcha'));
        return $response->withRedirect($this->router->pathFor('auth.login'));
      }
    }

    $a = new \App\Models\Accounts();
    $user = $a->where(['username' => $request->getParam('username')])->first();
    if($user){

      $token = $this->auth->createToken(15);
      $sys = $this->config;

      $r = new \App\Models\Reset();
      $reset = $r->get($token);
      $reset->token = $token;
      $reset->userid = $user['userid'];
      $reset->type = 'password';
      $reset->save();

      $this->mailer->addToQueue($user['email'], 'forgot', $sys['url']['host'] . $this->router->pathFor('auth.reset', ['token' => $token]));
    }

    $this->message->addInline('info', Translate::translate('login.msg.forgot'));
    return $response->withRedirect($this->router->pathFor('auth.forgot'));

  }

  public function getRegister($request, $response)
  {
    if(! $this->config['sys']['system']['register']){
      return $response->withRedirect($this->router->pathFor('auth.login'));
    }
    return $this->view->render($response, 'auth/register.twig');
  }

  public function postRegister($request, $response)
  {
    if($this->config['sys']['system']['register']){
      $validation = $this->validator->validate($request, [
        'username' => v::noWhitespace()->notEmpty()->usernameAvailable(),
        'mail' => v::notEmpty()->email()->emailAvailable(),
        'password' => v::noWhitespace()->notEmpty()->strengthPassword()
      ]);

      if ($validation->failed()) {
        return $response->withRedirect($this->router->pathFor('auth.register'));
      }

      if($this->config['sys']['secure']['captcha']['enabled']) {
        Recaptcha::setKeys($this->config['sys']['secure']['captcha']['key']['private'], $this->config['sys']['secure']['captcha']['key']['public']);
        Recaptcha::setVersion($this->config['sys']['secure']['captcha']['version']);

        if (Recaptcha::failed()) {
          $this->message->addInline('danger', Translate::translate('login.msg.captcha'));
          return $response->withRedirect($this->router->pathFor('auth.login'));
        }
      }

      $token = $this->auth->createToken(15);

      $a = new \App\Models\Accounts();
      $user = $a->get($token);
      $user->userid = $token;
      $user->username = $request->getParam('username');
      $user->email = $request->getParam('mail');
      $user->password_hash = $this->auth->createHash($request->getParam('password'));
      $user->role = ($a->count() == 0) ? 'admin' : 'user';
      $user->save();

      $this->mailer->addToQueue($user->email, 'welcome');
      $this->message->addInline('success', Translate::translate('login.msg.acccreated'));
    }

    return $response->withRedirect($this->router->pathFor('auth.login'));
  }

  public function getReset($request, $response, $args)
  {
    if(!$this->auth->allowedToResetPassword($args['token'])){
      return $response->withRedirect($this->router->pathFor('auth.login'));
    }

    return $this->view->render($response, 'auth/reset.twig');
  }

  public function postReset($request, $response, $args)
  {
    // Wenn das Token korrekt ist
    $reset = $this->auth->allowedToResetPassword($args['token']);
    if(!$reset){
      return $response->withRedirect($this->router->pathFor('auth.forget'));
    }

    // Prüfen ob ein neues Passwort eingegeben wurde
    $validation = $this->validator->validate($request, [
      'password' => v::noWhitespace()->notEmpty()
    ]);

    if ($validation->failed()) {
      return $response->withRedirect($this->router->pathFor('auth.reset', ['token' => $args['token']]));
    }

    // Prüfen ob der User zum Token noch existiert oder nicht
    // Wenn dieser nicht existiert, abbrechen.
    $user = $this->auth->user($reset['userid']);

    if(!$user){
      return $response->withRedirect($this->router->pathFor('auth.forget'));
    }

    // Neues Passwort verschlüsseln
    $password = $this->auth->createHash($request->getParam('password'));

    // E-Mail Benachrichtigung der warteschlange hinzufügen
    $this->mailer->addToQueue($user->email, 'password');

    // Neues und verschlüsseltes Passwort speichern
    $user->password_hash = $password;
    $user->save();

    // Token das zum ändern des Passwortes berechtigt löschen
    $r = new \App\Models\Reset();
    $r->where(['token' => $reset['token'], 'type' => 'password'])->delete();

    // Meldung anzeigen und alle Sessions des users löschen,
    // anschliessend auf die Login seite leiten
    $this->message->addInline('success', Translate::translate('login.msg.changepassword'));
    $this->auth->deleteAllUserToken($user->userid);
    return $response->withRedirect($this->router->pathFor('auth.login'));
  }
}

<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;

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

    $auth = $this->auth->login(
      $request->getParam('username'),
      $request->getParam('password')
    );

    if (! $auth['success']) {
      $this->flash->addMessage('error', 'Die Login Daten sind nicht korrekt!');
      return $response->withRedirect($this->router->pathFor('auth.login'));
    }

    return $response->withRedirect($this->router->pathFor('app.index'));
  }

  public function getLogout($request, $response)
  {
    $this->auth->logout();
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
    $user = $this->auth->getUserDB()->where(['username' => $request->getParam('username')])->first();
    if($user){

      $token = $this->auth->createToken(15);
      $sys = $this->config;

      $reset_db = new \Filebase\Database([
        'dir' => PATH_ROOT . DIR_TEMP . DIR_TEMP_RESET,
        'format' => \Filebase\Format\Yaml::class,
      ]);

      $reset = $reset_db->get($token);
      $reset->userid = $user['userid'];
      $reset->save();

      $this->mailer->addToQueue($user['email'], 'forgot', $sys['url']['host'] . $this->router->pathFor('auth.reset', ['token' => $token]));
    }

    $this->flash->addMessage('success', 'Sollte ein Account mit dieser Adresse vorhanden sein, so wird dir ein Link zum zurücksetzen des Passwortes per Mail zugeschickt.');
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
        'username' => v::noWhitespace()->notEmpty(),
        'vorname' => v::notEmpty(),
        'nachname' => v::notEmpty(),
        'password' => v::noWhitespace()->notEmpty()
      ]);

      if ($validation->failed()) {
        return $response->withRedirect($this->router->pathFor('auth.register'));
      }


    }

    return $response->withRedirect($this->router->pathFor('auth.login'));
  }

  public function getReset($request, $response, $args)
  {
    if(!$this->auth->allowedToResetPassword($args['token'])){
      $this->flash->addMessage('error', 'Keine Berechtigung zum Passwort zurücksetzen.');
      return $response->withRedirect($this->router->pathFor('auth.login'));
    }

    return $this->view->render($response, 'auth/reset.twig');
  }

  public function postReset($request, $response, $args)
  {
    $reset = $this->auth->allowedToResetPassword($args['token']);
    if(!$reset){
      return $response->withRedirect($this->router->pathFor('auth.forget'));
    }

    $validation = $this->validator->validate($request, [
      'password' => v::noWhitespace()->notEmpty()
    ]);

    if ($validation->failed()) {
      return $response->withRedirect($this->router->pathFor('auth.reset', ['token' => $args['token']]));
    }

    $user = $this->auth->user($reset->userid);

    if(!$user){
      return $response->withRedirect($this->router->pathFor('auth.forget'));
    }

    $password = $this->auth->createHash($request->getParam('password'));

    $this->mailer->addToQueue($user->email, 'password');

    $user->password_hash = $password;
    $user->save();

    $this->auth->getResetDB()->where(['userid' => $user->userid])->delete();

    $this->flash->addMessage('success', 'Dein Passwort wurde geändert.');
    $this->auth->deleteAllUserToken($user->userid);
    return $response->withRedirect($this->router->pathFor('auth.login'));
  }
}

<?php
use App\Middleware\GuestMiddleware;
use App\Middleware\UserMiddleware;

$app->group('', function () {
  $this->get('/', function($request, $response){
    return $response->withRedirect($this->router->pathFor('auth.login'));
  });
  $this->get('/forgot', 'AuthController:getForgot')->setName('auth.forgot');
  $this->post('/forgot', 'AuthController:postForgot')->setName('auth.forgot');
  $this->get('/login', 'AuthController:getLogin')->setName('auth.login');
  $this->post('/login', 'AuthController:postLogin')->setName('auth.login');
  $this->get('/reactivate/{token}', 'AuthController:getBruteReset')->setName('auth.brute');
  $this->get('/reset/{token}', 'AuthController:getReset')->setName('auth.reset');
  $this->post('/reset/{token}', 'AuthController:postReset')->setName('auth.reset');
  $this->get('/register', 'AuthController:getRegister')->setName('auth.register');
  $this->post('/register', 'AuthController:postRegister')->setName('auth.register');
})->add(new GuestMiddleware($container));


$app->group('/acp', function () {
  $this->get('/', 'HomeController:index')->setName('app.index');
  $this->get('/logout', 'AuthController:getLogout')->setName('auth.logout');



})->add(new UserMiddleware($container));

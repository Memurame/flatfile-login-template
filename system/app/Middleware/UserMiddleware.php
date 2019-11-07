<?php

namespace App\Middleware;

class UserMiddleware extends Middleware
{

	public function __invoke($request, $response, $next)
	{
    $ip = $_SERVER['REMOTE_ADDR'];
    $secure = $this->container->config['sys']['secure']['ip'];

    if($secure['enabled'] and !in_array($ip, $secure['allowed'])){
      return $this->container->view->render($response, 'error/403.twig');
    }

    $check = $this->container->auth->check();
    if(!$check['success']) {
      if($check['reason'] == 'EXPIRED'){
        $this->container->message->addInline('info', 'Du wurdes aufgrund Inaktivität ausgeloggt.');
      }
      return $response->withRedirect($this->container->router->pathFor('auth.login'));
    }



    $response = $next($request, $response);
    return $response;
	}
}

<?php

namespace App\Middleware;

class GuestMiddleware extends Middleware
{

	public function __invoke($request, $response, $next)
	{
    $ip = $_SERVER['REMOTE_ADDR'];
    $secure = $this->container->config['sys']['secure']['ip'];

    if($secure['enabled'] and !in_array($ip, $secure['allowed'])){
      return $this->container->view->render($response, 'error/403.twig');
    }

	  $check = $this->container->auth->check();
		if($check['success']) {
			return $response->withRedirect($this->container->router->pathFor('app.index'));
		}

		$response = $next($request, $response);
		return $response;
	}
}

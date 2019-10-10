<?php

namespace App\Middleware;

class GuestMiddleware extends Middleware
{

	public function __invoke($request, $response, $next)
	{
	  $check = $this->container->auth->check();
		if($check['success']) {
			return $response->withRedirect($this->container->router->pathFor('app.index'));
		}

		$response = $next($request, $response);
		return $response;
	}
}

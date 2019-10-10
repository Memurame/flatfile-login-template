<?php

namespace App\Middleware;

class UserMiddleware extends Middleware
{

	public function __invoke($request, $response, $next)
	{
        $check = $this->container->auth->check();
        if(!$check['success']) {
            if($check['msg'] == 'EXPIRED'){
                $this->container->flash->addMessage('error', 'Du wurdes aufgrund Inaktivität ausgeloggt.');
            }
            return $response->withRedirect($this->container->router->pathFor('auth.login'));
        }

        $response = $next($request, $response);
        return $response;
	}
}

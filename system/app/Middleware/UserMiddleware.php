<?php

namespace App\Middleware;

class UserMiddleware extends Middleware
{

	public function __invoke($request, $response, $next)
	{
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

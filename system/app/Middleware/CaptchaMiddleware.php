<?php

namespace App\Middleware;

class CaptchaMiddleware extends Middleware
{

	public function __invoke($request, $response, $next)
	{
    $route = $request->getAttribute('route');
    $sys = $this->container->config;

    // return NotFound for non existent route
    if (empty($route)) {
      throw new NotFoundException($request, $response);
    }

    $name = explode('.', $route->getName());
    $name = (!isset($name[1])) ? $name[0] : $name[1];


    $a = [];
    if($sys['sys']['secure']['captcha']['enabled']){
      if($sys['sys']['secure']['captcha']['version'] == '3')
      {
        $a = [
          'script' => "<script src='https://www.google.com/recaptcha/api.js?render=" . $sys['sys']['secure']['captcha']['key']['public'] . "'></script>
        <script>
          
          grecaptcha.ready(function () {
            grecaptcha.execute('".$sys['sys']['secure']['captcha']['key']['public']."', { action: '".$name."' }).then(function (token) {
              var recaptchaResponse = document.getElementById('recaptchaResponse');
              if(document.getElementById('recaptchaResponse')){
              recaptchaResponse.value = token;}
            });
          });
        </script>",
          'form' => "<input type='hidden' name='recaptcha_response' id='recaptchaResponse'>"
        ];
      }
      elseif($sys['sys']['secure']['captcha']['version'] == '2')
      {
        $a = [
          'script' => "<script src='https://www.google.com/recaptcha/api.js'></script>",
          'form' => '<div class="g-recaptcha" data-sitekey="'.$sys['sys']['secure']['captcha']['key']['public'].'"></div>'
        ];
      }


    }

		$this->container->view->getEnvironment()->addGlobal('captcha', $a);

		$response = $next($request, $response);
		return $response;
	}
}

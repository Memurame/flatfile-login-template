<?php

namespace App\Middleware;


use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class LocaleMiddleware extends Middleware
{

  public function __invoke($request, $response, $next)
  {
    $translator = new \App\Translate\Translate(PATH_ROOT . DIR_DATA . DIR_LOCALE);

    if($translator->fromGet()){
      $url = explode('?', $this->container->config['url']['current'])[0];
      return $response->withRedirect($url);
    }

    $l = $translator->getTranslation();
    $this->container->view->getEnvironment()->addGlobal('locale', $l['locale']);

    $response = $next($request, $response);
    return $response;
  }

}

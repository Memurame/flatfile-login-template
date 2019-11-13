<?php

namespace App\Middleware;

use App\Twig\LocaleExtension;

class LocaleMiddleware extends Middleware
{

  public function __invoke($request, $response, $next)
  {

    $files = scandir(PATH_SYSTEM . 'templates/locale');
    foreach($files as $key => $value){
      if (!in_array($value,array(".","..")) && !file_exists(PATH_LOCALE . $value )) {
        copy(
          PATH_SYSTEM . 'templates/locale/' . $value,
          PATH_ROOT . DIR_DATA . DIR_LOCALE . $value
        );
      }
    }

    $translator = new \App\Translate\Translate(PATH_ROOT . DIR_DATA . DIR_LOCALE, $this->container->config['sys']['system']['locale']['default']);

    if($translator->fromGet()){
      $url = explode('?', $this->container->config['url']['current'])[0];
      return $response->withRedirect($url);
    }



    $this->container->view->getEnvironment()->addGlobal('locale', $translator->getLocale());
    $this->container->view->addExtension(new LocaleExtension($translator));

    $response = $next($request, $response);
    return $response;
  }

}

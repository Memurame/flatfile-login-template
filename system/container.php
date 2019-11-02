<?php

use Slim\Container;
use Symfony\Component\Yaml\Yaml;

$container['config'] = function($container) {
  if(!file_exists(FILE_SETTINGS)){
    copy(
      PATH_SYSTEM . DIR_CONFIG . 'settings.yaml',
      FILE_SETTINGS
    );
  }
  $config['sys'] = Yaml::parseFile(FILE_SETTINGS);

  $config['backgroundTask'] = (defined('STDIN')) ? true : false;
  if(!$config['backgroundTask']){
    $config['url']['root'] = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $config['url']['host'] = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $config['url']['current'] = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $config['url']['public'] = $config['url']['host'] . $config['url']['root'];
  }
  return $config;
};

$container['mailer'] = function ($container) {
  $settings = $container->get('config');
  $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);

  if($settings['sys']['mail']['type'] == 'smtp'){
    $mailer->SMTPDebug = 0;
    $mailer->Host = $settings['sys']['mail']['host'];
    $mailer->SMTPAuth = $settings['sys']['mail']['smtp']['auth'];
    if($settings['sys']['mail']['smtp']['secure']) $mailer->SMTPSecure = $settings['sys']['mail']['smtp']['secure'];
    $mailer->Port = $settings['sys']['mail']['port'];
    $mailer->Username = $settings['sys']['mail']['user'];
    $mailer->Password = $settings['sys']['mail']['pass'];
    $mailer->CharSet = 'UTF-8';
    $mailer->isHTML(true);
    $mailer->isSMTP();
  } else {
    $mailer->isSendmail();
  }

  return new \App\Mail\Mailer(
    $container,
    $mailer,
    $settings,
    PATH_ROOT . DIR_TEMP . DIR_TEMP_QUEUE
  );
};

$container['auth'] = function($container) {
  $auth =  new \App\Auth\Auth;
  $auth->setTokenDir(PATH_ROOT . DIR_TEMP . DIR_TEMP_TOKEN);
  $auth->setResetDir(PATH_ROOT . DIR_TEMP . DIR_TEMP_RESET);
  $auth->setUserDir(PATH_ACCOUNTS);
  return $auth;
};


$container['view'] = function ($container) {
  $settings = $container->config;
  $path_root = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');

  $viewPath = PATH_ROOT . DIR_THEME . $settings['sys']['system']['theme'] . '/templates';
  $view = new \Slim\Views\Twig($viewPath, [
    'cache' => $settings['sys']['twig']['cache']['enabled'] ? PATH_ROOT . DIR_TEMP . 'twig-cache' : false
  ]);

  $view->addExtension(new \Slim\Views\TwigExtension($container->router, $container->request->getUri()));

  if(!$settings['backgroundTask']){
    $view->getEnvironment()->addGlobal('message', $container->message);
    $view->getEnvironment()->addGlobal('theme_url', $settings['url']['public'] . DS . DIR_THEME . $settings['sys']['system']['theme']);
  }

  $view->getEnvironment()->addGlobal('setting', $settings);

  $view->getEnvironment()->addGlobal('current_url', $container->request->getUri());

  return $view;
};

$container['validator'] = function ($container) {
  return new App\Validation\Validator;
};

$container['message'] = function ($container) {
  return new App\Message\Message($container);
};

$container['HomeController'] = function($container) {
  return new \App\Controllers\HomeController($container);
};

$container['AuthController'] = function($container) {
  return new \App\Controllers\AuthController($container);
};

$container['csrf'] = function($container) {
  $guard = new \Slim\Csrf\Guard();
  return $guard;
};

$container['notFoundHandler'] = function ($container) {
  return function ($request, $response) use ($container) {
    return $container['view']
      ->render($response, 'error/404.twig');
  };
};

$container['notAllowedHandler'] = function ($container) {
  return function ($request, $response, $methods) use ($container) {
    return $container['response']
      ->withRedirect($container->router->pathFor('auth.login'));
  };
};

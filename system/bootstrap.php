<?php

use Respect\Validation\Validator as v;
session_start();

require_once realpath(__DIR__ ) . '/define.php';

require_once realpath(__DIR__ . '/../') . '/vendor/autoload.php';

/*
 * Slim instanz starten und die container laden
 */
$app = new \Slim\App(['settings' => require __DIR__ . '/settings.php']);
$container = $app->getContainer();

require  __DIR__ . '/container.php';

$app->add(new \App\Middleware\ValidationErrorsMiddleware($container));
$app->add(new \App\Middleware\OldInputMiddleware($container));
$app->add(new \App\Middleware\CsrfViewMiddleware($container));
$app->add(new \App\Middleware\CaptchaMiddleware($container));

//$app->add($container->csrf);

v::with('App\\Validation\\Rules\\');

require __DIR__ . '/routes.php';

return $app;

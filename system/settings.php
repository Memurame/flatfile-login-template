<?php

$settings = [];

$debug =  realpath(__DIR__ . '/../') . '/.DEBUG';

// Slim settings
$settings['displayErrorDetails'] = (file_exists($debug)) ? true : false;
$settings['determineRouteBeforeAppMiddleware'] = true;

return $settings;

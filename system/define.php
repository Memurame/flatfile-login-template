<?php

/*
 * Set directories
 */
define('VERSION', '1.0.0');
define('DS', '/');

if (!defined('PATH_ROOT')) {
  define('PATH_ROOT', str_replace(DIRECTORY_SEPARATOR, DS, realpath(__DIR__ . '../../')) . '/');
}

define('DIR_TEMP', 'tmp/');
define('DIR_LOG', 'logs/');
define('DIR_SYSTEM', 'system/');
define('DIR_CONFIG', 'config/');
define('DIR_DATA', 'data/');
define('DIR_THEME', 'themes/');
define('DIR_ACCOUNTS', 'accounts/');
define('DIR_TEMP_QUEUE', 'queue/');
define('DIR_TEMP_TOKEN', 'token/');
define('DIR_TEMP_RESET', 'reset/');

/*
 * Set paths
 */
define('PATH_SYSTEM', PATH_ROOT . DIR_SYSTEM);
define('PATH_CONFIG', PATH_ROOT . DIR_DATA  . DIR_CONFIG);
define('PATH_ACCOUNTS', PATH_ROOT . DIR_DATA  . DIR_ACCOUNTS);


/*
 * Set file
 */

define('FILE_SETTINGS', PATH_CONFIG . 'settings.yaml');

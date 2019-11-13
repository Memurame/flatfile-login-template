<?php

/*
 * Set directories
 */
define('VERSION', '0.9.1');
define('DS', '/');

if (!defined('PATH_ROOT')) {
  define('PATH_ROOT', str_replace(DIRECTORY_SEPARATOR, DS, realpath(__DIR__ . '/../')) . '/');
}

define('DIR_TEMP', 'tmp/');
define('DIR_LOG', 'logs/');
define('DIR_SYSTEM', 'system/');
define('DIR_LOCALE', 'locale/');
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
define('PATH_ACCOUNTS', PATH_ROOT . DIR_DATA  . DIR_ACCOUNTS);
define('PATH_LOCALE', PATH_ROOT . DIR_DATA  . DIR_LOCALE);


/*
 * Set file
 */

define('FILE_SETTINGS', PATH_ROOT . 'settings.yaml');

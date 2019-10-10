<?php

require_once realpath(__DIR__ ) . '/system/define.php';

require_once realpath(__DIR__ ) . '/vendor/autoload.php';

use GO\Scheduler;

$scheduler = new Scheduler();

$scheduler->php(PATH_ROOT . DIR_SYSTEM . 'cronjobs/email.php');

$scheduler->run();


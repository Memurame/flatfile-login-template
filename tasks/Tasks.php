<?php

require_once realpath(__DIR__ . '/../') . '/system/define.php';

require_once realpath(__DIR__ . '/../') . '/vendor/autoload.php';

use Crunz\Schedule;

$schedule = new Schedule();
$task = $schedule->run(PHP_BINARY . ' ' . PATH_ROOT . DIR_SYSTEM . 'cronjobs/email.php');
$task->everyMinute();

return $schedule;


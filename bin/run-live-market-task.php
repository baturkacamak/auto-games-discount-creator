<?php

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

$type = isset($args[0]) ? (string) $args[0] : '';
$marketKey = isset($args[1]) ? (string) $args[1] : '';

if (!in_array($type, ['daily', 'hourly'], true) || $marketKey === '') {
	exit("Usage: wp eval-file run-live-market-task.php <daily|hourly> <market-key>\n");
}

$module = new \AutoGamesDiscountCreator\Modules\ScheduleModule();

if ($type === 'daily') {
	$module->runDailyMarketTask($marketKey);
} else {
	$module->runHourlyMarketTask($marketKey);
}

echo "Completed {$type} market task for {$marketKey}.\n";

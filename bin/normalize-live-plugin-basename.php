<?php

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

$activePlugins = get_option('active_plugins', []);
if (!is_array($activePlugins)) {
	$activePlugins = [];
}

$normalized = [];
foreach ($activePlugins as $pluginFile) {
	$pluginFile = (string) $pluginFile;
	if ($pluginFile === '' || $pluginFile === 'rambouillet/rambouillet.php' || $pluginFile === 'rambouillet/autogamesdiscountcreator.php') {
		continue;
	}

	$normalized[] = $pluginFile;
}

$normalized[] = 'auto-games-discount-creator/autogamesdiscountcreator.php';
$normalized = array_values(array_unique($normalized));

update_option('active_plugins', $normalized, false);

echo "Normalized active_plugins.\n";

<?php

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

$settings = get_option('agdc_settings', []);
if (!is_array($settings)) {
	$settings = [];
}

$settings['general'] = array_merge(
	is_array($settings['general'] ?? null) ? $settings['general'] : [],
	[
		'enabled' => true,
		'dry_run' => false,
	]
);

$settings['posting'] = array_merge(
	is_array($settings['posting'] ?? null) ? $settings['posting'] : [],
	[
		'author_id' => 1,
		'post_status' => 'publish',
		'daily_post_time' => '06:00',
	]
);

$settings['posting_daily'] = array_merge(
	is_array($settings['posting_daily'] ?? null) ? $settings['posting_daily'] : [],
	[
		'author_id' => 1,
		'post_status' => 'publish',
	]
);

$settings['posting_free'] = array_merge(
	is_array($settings['posting_free'] ?? null) ? $settings['posting_free'] : [],
	[
		'author_id' => 1,
		'post_status' => 'publish',
	]
);

$settings['data_model'] = array_merge(
	is_array($settings['data_model'] ?? null) ? $settings['data_model'] : [],
	[
		'default_market_target_key' => 'tr-tr',
		'rollout_market_target_keys' => ['tr-tr', 'en-gb', 'en-us', 'es-es', 'es-mx'],
		'default_discount_store_key' => 'steam',
		'default_free_store_key' => 'epic',
		'daily_repeat_window_days' => 7,
		'free_repeat_window_days' => 7,
	]
);

$settings['source'] = array_merge(
	is_array($settings['source'] ?? null) ? $settings['source'] : [],
	[
		'itad_country_code' => 'TR',
		'itad_currency_code' => 'TRY',
	]
);

update_option('agdc_settings', $settings, false);

echo "Updated agdc_settings.\n";

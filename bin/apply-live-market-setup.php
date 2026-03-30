<?php

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

global $wpdb;

$languages = [
	[
		'code' => 'tr-tr',
		'english_name' => 'Turkish (Turkey)',
		'major' => 0,
		'active' => 1,
		'default_locale' => 'tr_TR',
		'tag' => 'tr-TR',
		'encode_url' => 0,
		'country' => 'TR',
	],
	[
		'code' => 'en-gb',
		'english_name' => 'English (United Kingdom)',
		'major' => 0,
		'active' => 1,
		'default_locale' => 'en_GB',
		'tag' => 'en-GB',
		'encode_url' => 0,
		'country' => 'GB',
	],
	[
		'code' => 'en-us',
		'english_name' => 'English (United States)',
		'major' => 1,
		'active' => 1,
		'default_locale' => 'en_US',
		'tag' => 'en-US',
		'encode_url' => 0,
		'country' => 'US',
	],
	[
		'code' => 'es-es',
		'english_name' => 'Spanish (Spain)',
		'major' => 1,
		'active' => 1,
		'default_locale' => 'es_ES',
		'tag' => 'es-ES',
		'encode_url' => 0,
		'country' => 'ES',
	],
	[
		'code' => 'es-mx',
		'english_name' => 'Spanish (Mexico)',
		'major' => 0,
		'active' => 1,
		'default_locale' => 'es_MX',
		'tag' => 'es-MX',
		'encode_url' => 0,
		'country' => 'MX',
	],
];

$languageTranslations = [
	['language_code' => 'tr-tr', 'display_language_code' => 'tr-tr', 'name' => 'Türkçe (Türkiye)'],
	['language_code' => 'tr-tr', 'display_language_code' => 'tr', 'name' => 'Türkçe (Türkiye)'],
	['language_code' => 'tr-tr', 'display_language_code' => 'en', 'name' => 'Turkish (Turkey)'],
	['language_code' => 'tr-tr', 'display_language_code' => 'en-us', 'name' => 'Turkish (Turkey)'],
	['language_code' => 'tr-tr', 'display_language_code' => 'en-gb', 'name' => 'Turkish (Turkey)'],
	['language_code' => 'tr-tr', 'display_language_code' => 'es', 'name' => 'Turco (Turquía)'],
	['language_code' => 'tr-tr', 'display_language_code' => 'es-es', 'name' => 'Turco (Turquía)'],
	['language_code' => 'tr-tr', 'display_language_code' => 'es-mx', 'name' => 'Turco (Turquía)'],
	['language_code' => 'en-us', 'display_language_code' => 'tr', 'name' => 'İngilizce (ABD)'],
	['language_code' => 'en-us', 'display_language_code' => 'en', 'name' => 'English (United States)'],
	['language_code' => 'en-us', 'display_language_code' => 'tr-tr', 'name' => 'İngilizce (ABD)'],
	['language_code' => 'en-us', 'display_language_code' => 'en-us', 'name' => 'English (United States)'],
	['language_code' => 'en-us', 'display_language_code' => 'en-gb', 'name' => 'English (United States)'],
	['language_code' => 'en-us', 'display_language_code' => 'es', 'name' => 'Inglés (Estados Unidos)'],
	['language_code' => 'en-us', 'display_language_code' => 'es-es', 'name' => 'Inglés (Estados Unidos)'],
	['language_code' => 'en-us', 'display_language_code' => 'es-mx', 'name' => 'Inglés (Estados Unidos)'],
	['language_code' => 'en-gb', 'display_language_code' => 'tr', 'name' => 'İngilizce (Birleşik Krallık)'],
	['language_code' => 'en-gb', 'display_language_code' => 'en', 'name' => 'English (United Kingdom)'],
	['language_code' => 'en-gb', 'display_language_code' => 'tr-tr', 'name' => 'İngilizce (Birleşik Krallık)'],
	['language_code' => 'en-gb', 'display_language_code' => 'en-us', 'name' => 'English (United Kingdom)'],
	['language_code' => 'en-gb', 'display_language_code' => 'en-gb', 'name' => 'English (United Kingdom)'],
	['language_code' => 'en-gb', 'display_language_code' => 'es', 'name' => 'Inglés (Reino Unido)'],
	['language_code' => 'en-gb', 'display_language_code' => 'es-es', 'name' => 'Inglés (Reino Unido)'],
	['language_code' => 'en-gb', 'display_language_code' => 'es-mx', 'name' => 'Inglés (Reino Unido)'],
	['language_code' => 'es-es', 'display_language_code' => 'tr', 'name' => 'İspanyolca (İspanya)'],
	['language_code' => 'es-es', 'display_language_code' => 'en', 'name' => 'Spanish (Spain)'],
	['language_code' => 'es-es', 'display_language_code' => 'tr-tr', 'name' => 'İspanyolca (İspanya)'],
	['language_code' => 'es-es', 'display_language_code' => 'en-us', 'name' => 'Spanish (Spain)'],
	['language_code' => 'es-es', 'display_language_code' => 'en-gb', 'name' => 'Spanish (Spain)'],
	['language_code' => 'es-es', 'display_language_code' => 'es', 'name' => 'Español (España)'],
	['language_code' => 'es-es', 'display_language_code' => 'es-es', 'name' => 'Español (España)'],
	['language_code' => 'es-es', 'display_language_code' => 'es-mx', 'name' => 'Español (España)'],
	['language_code' => 'es-mx', 'display_language_code' => 'tr', 'name' => 'İspanyolca (Meksika)'],
	['language_code' => 'es-mx', 'display_language_code' => 'en', 'name' => 'Spanish (Mexico)'],
	['language_code' => 'es-mx', 'display_language_code' => 'tr-tr', 'name' => 'İspanyolca (Meksika)'],
	['language_code' => 'es-mx', 'display_language_code' => 'en-us', 'name' => 'Spanish (Mexico)'],
	['language_code' => 'es-mx', 'display_language_code' => 'en-gb', 'name' => 'Spanish (Mexico)'],
	['language_code' => 'es-mx', 'display_language_code' => 'es', 'name' => 'Español (México)'],
	['language_code' => 'es-mx', 'display_language_code' => 'es-es', 'name' => 'Español (México)'],
	['language_code' => 'es-mx', 'display_language_code' => 'es-mx', 'name' => 'Español (México)'],
];

$localeMap = [
	['code' => 'tr-tr', 'locale' => 'tr_TR'],
	['code' => 'en-us', 'locale' => 'en_US'],
	['code' => 'en-gb', 'locale' => 'en_GB'],
	['code' => 'es-mx', 'locale' => 'es_MX'],
];

$flags = [
	['lang_code' => 'tr-tr', 'flag' => 'tr.png', 'from_template' => 0],
	['lang_code' => 'en-us', 'flag' => 'us.png', 'from_template' => 0],
	['lang_code' => 'en-gb', 'flag' => 'gb.png', 'from_template' => 0],
	['lang_code' => 'es-es', 'flag' => 'es.png', 'from_template' => 0],
	['lang_code' => 'es-mx', 'flag' => 'mx.png', 'from_template' => 0],
];

$activeCodes = array_column($languages, 'code');

foreach ($languages as $language) {
	$existingId = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}icl_languages WHERE code = %s LIMIT 1",
			$language['code']
		)
	);

	if ($existingId > 0) {
		$language['id'] = $existingId;
	}

	$wpdb->replace($wpdb->prefix . 'icl_languages', $language);
	echo 'Synced language: ' . $language['code'] . PHP_EOL;
}

$wpdb->query(
	$wpdb->prepare(
		"UPDATE {$wpdb->prefix}icl_languages SET active = 0 WHERE code IN (%s, %s)",
		'tr',
		'en'
	)
);

foreach ($languageTranslations as $translation) {
	$existingId = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}icl_languages_translations WHERE language_code = %s AND display_language_code = %s LIMIT 1",
			$translation['language_code'],
			$translation['display_language_code']
		)
	);

	if ($existingId > 0) {
		$translation['id'] = $existingId;
	}

	$wpdb->replace($wpdb->prefix . 'icl_languages_translations', $translation);
}

foreach ($localeMap as $localeRow) {
	$wpdb->replace($wpdb->prefix . 'icl_locale_map', $localeRow);
}

foreach ($flags as $flag) {
	$existingId = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}icl_flags WHERE lang_code = %s LIMIT 1",
			$flag['lang_code']
		)
	);

	if ($existingId > 0) {
		$flag['id'] = $existingId;
	}

	$wpdb->replace($wpdb->prefix . 'icl_flags', $flag);
}

$settings = get_option('icl_sitepress_settings', []);
if (!is_array($settings)) {
	$settings = [];
}

$settings['language_negotiation_type'] = 1;
$settings['urls'] = array_merge(
	is_array($settings['urls'] ?? null) ? $settings['urls'] : [],
	[
		'directory_for_default_language' => 1,
		'show_on_root' => '',
		'root_html_file_path' => '',
		'root_page' => 0,
		'hide_language_switchers' => 1,
	]
);
$settings['active_languages'] = ['en-us', 'es-es', 'tr-tr', 'en-gb', 'es-mx'];
$settings['default_language'] = 'tr-tr';
$settings['languages_order'] = ['tr-tr', 'en-gb', 'en-us', 'es-es', 'es-mx'];
$settings['admin_default_language'] = '_default_';
$settings['default_categories'] = [
	'tr-tr' => '1',
	'en-us' => 2,
	'en-gb' => 2,
	'es-es' => 0,
	'es-mx' => 0,
];

$settings['custom_posts_sync_option'] = array_merge(
	is_array($settings['custom_posts_sync_option'] ?? null) ? $settings['custom_posts_sync_option'] : [],
	[
		'post' => 1,
		'page' => 1,
		'attachment' => 1,
		'wp_block' => 1,
		'wp_navigation' => 1,
		'wp_template' => 1,
		'wp_template_part' => 1,
		'agdc_roundup' => 1,
	]
);

$settings['taxonomies_sync_option'] = array_merge(
	is_array($settings['taxonomies_sync_option'] ?? null) ? $settings['taxonomies_sync_option'] : [],
	[
		'category' => 1,
		'post_tag' => 1,
		'translation_priority' => 1,
		'wp_theme' => 0,
	]
);

$tm = is_array($settings['translation-management'] ?? null) ? $settings['translation-management'] : [];
$tm['custom_fields_translation'] = array_merge(
	is_array($tm['custom_fields_translation'] ?? null) ? $tm['custom_fields_translation'] : [],
	[
		'footnotes' => 2,
		'_agdc_content_kind' => 1,
		'_agdc_snapshot_payload' => 1,
	]
);
$tm['custom-types_readonly_config'] = array_merge(
	is_array($tm['custom-types_readonly_config'] ?? null) ? $tm['custom-types_readonly_config'] : [],
	[
		'attachment' => 1,
		'wp_block' => 1,
		'wp_navigation' => 1,
		'wp_template' => 1,
		'wp_template_part' => 1,
		'agdc_roundup' => 1,
	]
);
$tm['taxonomies_readonly_config'] = array_merge(
	is_array($tm['taxonomies_readonly_config'] ?? null) ? $tm['taxonomies_readonly_config'] : [],
	[
		'translation_priority' => 1,
		'wp_theme' => 0,
	]
);
$tm['custom_fields_encoding'] = array_merge(
	is_array($tm['custom_fields_encoding'] ?? null) ? $tm['custom_fields_encoding'] : [],
	[
		'footnotes' => 'json',
	]
);

$readonly = is_array($tm['custom_fields_readonly_config'] ?? null) ? $tm['custom_fields_readonly_config'] : [];
foreach (['footnotes', '_agdc_content_kind', '_agdc_snapshot_payload'] as $field) {
	if (!in_array($field, $readonly, true)) {
		$readonly[] = $field;
	}
}
$tm['custom_fields_readonly_config'] = array_values($readonly);
$tm['__custom_fields_readonly_config_prev'] = $tm['custom_fields_readonly_config'];
$settings['translation-management'] = $tm;

if (isset($sitepress) && is_object($sitepress) && method_exists($sitepress, 'save_settings')) {
	$sitepress->save_settings($settings);
} else {
	update_option('icl_sitepress_settings', $settings);
}

echo "Updated icl_sitepress_settings.\n";

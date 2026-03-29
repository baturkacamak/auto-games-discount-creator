<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 18/2/23
 * Time: 22:35
 */

const AGDC_VERSION = '0.1.0';
define('AGDC_PLUGIN_DIR', dirname(__DIR__));
const AGDC_PLUGIN_FILE = AGDC_PLUGIN_DIR . '/autogamesdiscountcreator.php';
const AGDC_SETTINGS_OPTION = 'agdc_settings';
const AGDC_RUNTIME_STATE_OPTION = 'agdc_runtime_state';
const AGDC_SCHEMA_VERSION = '2026-03-28-02';
const AGDC_SCHEMA_VERSION_OPTION = 'agdc_schema_version';
const AGDC_SETTINGS_FILE = AGDC_PLUGIN_DIR . '/settings.json';
if (function_exists('plugin_dir_url')) {
	define('AGDC_PLUGIN_URL', plugin_dir_url(__FILE__));
}

defined('AGDC_BASE_FILE') or define(
	'AGDC_BASE_FILE',
	str_replace(dirname(AGDC_PLUGIN_FILE, 2) . '/', '', AGDC_PLUGIN_FILE)
);

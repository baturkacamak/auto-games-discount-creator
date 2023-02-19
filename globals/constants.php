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
if (function_exists('plugin_dir_url')) {
	define('AGDC_PLUGIN_URL', plugin_dir_url(__FILE__));
}

defined('AGDC_BASE_FILE') or define(
	'AGDC_BASE_FILE',
	str_replace(dirname(AGDC_PLUGIN_FILE, 2) . '/', '', AGDC_PLUGIN_FILE)
);

<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 18/2/23
 * Time: 22:35
 */

define('AGDC_VERSION', '0.1.0');
define('AGDC_PLUGIN_FILE', __FILE__);
define('AGDC_PLUGIN_DIR', __DIR__);
if (function_exists('plugin_dir_url')) {
	define('AGDC_PLUGIN_URL', plugin_dir_url(__FILE__));
}

defined('AGDC_BASE_FILE') or define(
	'AGDC_BASE_FILE',
	str_replace(dirname(__FILE__, 2) . '/', '', __FILE__)
);

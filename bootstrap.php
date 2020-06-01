<?php

/*
 * Plugin Name: Rambouillet (ITAD Scraper)
 * Version: 0.1
 * Author: Batur Kacamak
 * Description: This plugin will scrape data from ITAD
 * Text Domain: rambouillet
*/

use Rambouillet\Rambouillet;

require_once dirname(__FILE__) . '/vendor/autoload.php';
defined('RAMBUILLET_FILE') or define(
    'RAMBUILLET_FILE',
    str_replace(dirname(__FILE__, 2) . '/', '', __FILE__)
);
Rambouillet::getInstance()->init();

<?php

namespace Rambouillet;

use Curl\Curl;
use GuzzleHttp\Client;
use Rambouillet\Unyson\PluginSettings;

if (!class_exists('Rambouillet\Rambouillet')) {
	/**
	 * Class Rambouillet
	 *
	 * @package Rambouillet
	 */
	class Rambouillet extends AbstractRambouillet
	{

		/**
		 * The one true instance.
		 */
		private static $instance;
		/**
		 * @var Client
		 */
		public $guzzle;
		/**
		 * @var Settings
		 */
		public $settings;

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			parent::__construct();

			return self::$instance = $this;
		}

		/**
		 * Get singleton instance.
		 *
		 * @since 1.5
		 */
		public static function getInstance()
		{
			if (!isset(self::$instance)) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 *
		 */
		public function init()
		{
			if (!function_exists('is_plugin_active')) {
				include_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
		}

		public function addActions()
		{
			add_action('after_setup_theme', [$this, 'actionInit']);
			add_action('startScheduleDailyPost', ['Rambouillet\SchedulingTask', 'startDailyPostTask']);
			add_action('startScheduleHourlyPost', ['Rambouillet\SchedulingTask', 'startHourlyPostTask']);
		}

		/**
		 *
		 */
		public function actionInit()
		{
			if (defined('DOING_AJAX') && DOING_AJAX) {
				return false;
			}
			$this->settings = new Settings('rambouillet-settings');
			new Schedule();
		}
	}
}

<?php

namespace AutoGamesDiscountCreator;

use AutoGamesDiscountCreator\Core\ModulesManager;
use AutoGamesDiscountCreator\Core\Utility\JsonParser;
use GuzzleHttp\Client;

if (!class_exists('AutoGamesDiscountCreator\AutoGamesDiscountCreator')) {
	/**
	 * Class AutoGamesDiscountCreator
	 *
	 * @package AutoGamesDiscountCreator
	 */
	class AutoGamesDiscountCreator
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
		 * @var array Settings
		 */
		public $settings;
		/**
		 * @var
		 */
		private $pluginSettings;

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			$this->settings = (new JsonParser(AGDC_PLUGIN_DIR . '/settings.json'))->parse();

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
			new ModulesManager();
		}
	}
}

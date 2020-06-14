<?php

namespace Rambouillet;

use Curl\Curl;
use GuzzleHttp\Client;
use Rambouillet\Unyson\PluginSettings;
use Rambouillet\Utility\Medoo;

if (!class_exists('Rambouillet\Rambouillet')) {
    /**
     * Class Rambouillet
     *
     * @package Rambouillet
     */
    class Rambouillet
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
         * @var PluginSettings
         */
        public $pluginSettings;

        /**
         * Constructor.
         */
        protected function __construct()
        {
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

            $this->actions();
        }

        /**
         *
         */
        private function actions()
        {
            if (is_plugin_active(RAMBUILLET_FILE)) {
                add_action('init', [$this, 'actionInit']);

                // schedule actions
                add_action('startScheduleDailyPost', ['Rambouillet\Schedule', 'startDailyPost']);
                add_action('startScheduleHourlyPost', ['Rambouillet\Schedule', 'startHourlyPost']);
            } else {
                register_activation_hook(RAMBUILLET_FILE, ['Setup', 'init']);
            }
        }

        /**
         *
         */
        public function actionInit()
        {

            if (defined('DOING_AJAX') && DOING_AJAX) {
                return false;
            }

            new Requirement();

            if (defined('FW')) {
                $this->pluginSettings = new PluginSettings('rambouillet');

                new Schedule();
            }
        }
    }
}

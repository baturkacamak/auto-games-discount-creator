<?php

namespace Rambouillet;

use Curl\Curl;
use Rambouillet\Unyson\PluginSettings;

if (! class_exists('Rambouillet\Schedule')) {
    /**
     * Class Schedule
     *
     * @package Rambouillet
     */
    class Schedule
    {
        /**
         * @var Curl
         */
        private $curl;
        /**
         * @var PluginSettings
         */
        private $pluginSettings;

        /**
         * Schedule constructor.
         *
         * @param Curl           $curl
         * @param PluginSettings $pluginSettings
         */
        public function __construct(Curl $curl, PluginSettings $pluginSettings)
        {
            $this->curl           = $curl;
            $this->pluginSettings = $pluginSettings;

            $this->initSchedules();
        }

        /**
         *
         */
        public function startPosting()
        {
            new Poster($this->curl, $this->pluginSettings);
        }

        /**
         *
         */
        public function startScraping()
        {
            new Scrape($this->curl, $this->pluginSettings);
        }

        /**
         * @param $schedules
         *
         * @return mixed
         */
        public function addSchedule($schedules)
        {
            $schedules['everySixHours'] = [
                'interval' => 21600, // Every 6 hours
                'display'  => __('Every 6 hours', 'rambouillet'),
            ];

            return $schedules;
        }

        /**
         *
         */
        public function initSchedules()
        {
            add_filter('cron_schedules', [$this, 'addSchedule']);
            add_action('startScrapingAction', [$this, 'startScraping']);
            add_action('startPostingAction', [$this, 'startPosting']);

            if (! wp_next_scheduled('startScrapingAction')) {
                wp_schedule_event(time(), 'everySixHours', 'startScrapingAction');
            }

            if (! wp_next_scheduled('startPostingAction')) {
                wp_schedule_event(strtotime('06:00:00'), 'daily', 'startPostingAction');
            }
        }
    }
}

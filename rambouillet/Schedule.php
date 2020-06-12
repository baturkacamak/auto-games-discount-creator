<?php

namespace Rambouillet;

use Curl\Curl;
use Rambouillet\Unyson\PluginSettings;

if (!class_exists('Rambouillet\Schedule')) {
    /**
     * Class Schedule
     *
     * @package Rambouillet
     */
    class Schedule
    {
        /**
         * Schedule constructor.
         *
         */
        public function __construct()
        {
            $this->initSchedules();
        }

        /**
         *
         */
        public static function startDailyPost()
        {
            new Scrape();
            new Poster('daily');
        }

        /**
         *
         */
        public static function startHourlyPost()
        {
            new Scrape('hourly');
            new Poster('hourly');
        }

        /**
         *
         */
        public function initSchedules()
        {
            if (!wp_next_scheduled('startScheduleDailyPost')) {
                wp_schedule_event(strtotime('06:00:00'), 'daily', 'startScheduleDailyPost');
            }

            if (!wp_next_scheduled('startScheduleHourlyPost')) {
                wp_schedule_event(time(), 'hourly', 'startScheduleHourlyPost');
            }
        }
    }
}

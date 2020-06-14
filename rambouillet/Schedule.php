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
         * @throws \GuzzleHttp\Exception\GuzzleException
         * @throws \Exception
         */
        public static function startDailyPost()
        {
            $scrape = new Scraper();

            $results = $scrape->getQueryResults();
            $parser = new Parser();
            $parser->setQueryResults($results);
            $parser->parseQueryResults();
            new Poster();
        }

        /**
         *
         * @throws \GuzzleHttp\Exception\GuzzleException
         * @throws \Exception
         */
        public static function startHourlyPost()
        {
            $scrape = new Scraper('hourly');

            $results = $scrape->getQueryResults();
            $parser = new Parser();
            $parser->setQueryResults($results);
            $parser->parseQueryResults();

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

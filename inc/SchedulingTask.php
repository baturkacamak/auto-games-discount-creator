<?php

namespace Rambouillet;

use Curl\Curl;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Rambouillet\Unyson\PluginSettings;
use Rambouillet\Utility\Medoo;

if (!class_exists('Rambouillet\SchedulingTask')) {
	/**
	 * Class SchedulingTask
	 *
	 * @package Rambouillet
	 */
	class SchedulingTask extends AbstractRambouillet
	{
		/**
		 * Starts daily post
		 *
		 * @throws GuzzleException
		 * @throws Exception
		 */
		public static function startDailyPostTask()
		{
			$scrape  = new Scraper('daily', Rambouillet::getInstance()->settings);
			$results = $scrape->getQueryResults();
			$parser  = new Parser($results);
			$parser->parseQueryResults();
			new Poster();
		}

		/**
		 * Starts hourly post
		 *
		 * @throws GuzzleException
		 * @throws Exception
		 */
		public static function startHourlyPostTask()
		{
			$scrape  = new Scraper('hourly', Rambouillet::getInstance()->settings);
			$results = $scrape->getQueryResults();
			$parser  = new Parser($results);
			$parser->parseQueryResults();
			new Poster('hourly');
		}

		/**
		 * Adds actions
		 */
		public function addSchedulingTasks()
		{
			if (!wp_next_scheduled('startDailyPostTask')) {
				wp_schedule_event(strtotime('06:00:00'), 'daily', 'startDailyPostTask');
			}

			if (!wp_next_scheduled('startHourlyPostTask')) {
				wp_schedule_event(time(), 'hourly', 'startHourlyPostTask');
			}
		}
	}
}

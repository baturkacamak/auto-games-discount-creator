<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 19:48
 */

namespace AutoGamesDiscountCreator\Modules;

use AutoGamesDiscountCreator\Core\Module\AbstractModule;
use AutoGamesDiscountCreator\Core\Utility\Date;
use AutoGamesDiscountCreator\Core\Utility\GameDataParser;
use AutoGamesDiscountCreator\Core\Utility\GameInformationDatabase;
use AutoGamesDiscountCreator\Core\Utility\Scraper;
use AutoGamesDiscountCreator\Core\Utility\UtilityFactory;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use AutoGamesDiscountCreator\Post\Poster;
use AutoGamesDiscountCreator\Post\Strategy\DailyPostStrategy;
use AutoGamesDiscountCreator\Post\Strategy\FreeGamesPostStrategy;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

class ScheduleModule extends AbstractModule
{
	/**
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public function startDailyPostTask()
	{
		$game_informations   = $this->fetchGameInformations();
		$wordpress_functions = new WordPressFunctions();
		$date                = new Date();
		$daily_strategy      = new DailyPostStrategy($game_informations, $wordpress_functions, $date);
		(new Poster($daily_strategy))->post();
	}

	/**
	 * Starts hourly post
	 *
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public function startHourlyPostTask()
	{
		$game_informations   = $this->fetchGameInformations('hourly');
		$wordpress_functions = new WordPressFunctions();
		$utility_factory     = new UtilityFactory();
		if ($game_informations) {
			foreach ($game_informations as $index => $game_information) {
				$free_games_post_strategy = new FreeGamesPostStrategy(
					$game_information,
					$utility_factory,
					$wordpress_functions
				);
				(new Poster($free_games_post_strategy))->post();
			}
		}
	}

	public function setup()
	{
		$this->wpFunctions->scheduleEvent('startScheduleHourlyPost', 'hourly', 'startHourlyPostTask');
		$this->wpFunctions->scheduleEvent('startDailyPostTask', 'daily', 'startDailyPostTask', strtotime('06:00:00'));
	}

	/**
	 * @return array
	 * @throws GuzzleException
	 */
	private function fetchGameInformations($fetchType = 'daily'): array
	{
		$query_results = (new Scraper($fetchType))->getQueryResults();
		foreach ($query_results as $index => $query_result) {
			$game_informations = (new GameDataParser())->parseCacheResult($query_result);
			if ($game_informations) {
				foreach ($game_informations as $index => $game_information) {
					$game_information['price_id'] = (new GameInformationDatabase())->insertGameInformation
					(
						$game_information
					);
					$game_informations[$index]    = $game_information;
				}
			}
		}

		return $game_informations;
	}
}

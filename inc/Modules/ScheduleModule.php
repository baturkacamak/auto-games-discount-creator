<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 19:48
 */

namespace AutoGamesDiscountCreator\Modules;

use AutoGamesDiscountCreator\Core\Module\AbstractModule;
use AutoGamesDiscountCreator\Core\Settings\MarketTargetRepository;
use AutoGamesDiscountCreator\Core\Settings\RuntimeStateRepository;
use AutoGamesDiscountCreator\Core\Settings\SettingsRepository;
use AutoGamesDiscountCreator\Core\Utility\Date;
use AutoGamesDiscountCreator\Core\Utility\GameInformationDatabase;
use AutoGamesDiscountCreator\Core\Utility\OfferSelectionService;
use AutoGamesDiscountCreator\Core\Utility\Scraper;
use AutoGamesDiscountCreator\Core\Utility\UtilityFactory;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use AutoGamesDiscountCreator\Post\Poster;
use AutoGamesDiscountCreator\Post\Strategy\DailyPostStrategy;
use AutoGamesDiscountCreator\Post\Strategy\FreeGamesPostStrategy;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Throwable;

class ScheduleModule extends AbstractModule
{
	private RuntimeStateRepository $runtimeStateRepository;
	private OfferSelectionService $offerSelectionService;

	public function __construct($wpFunctions = null)
	{
		parent::__construct($wpFunctions);
		$this->runtimeStateRepository = new RuntimeStateRepository();
		$this->offerSelectionService = new OfferSelectionService();
	}

	/**
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public function startDailyPostTask()
	{
		if (!$this->isAutomationEnabled()) {
			$this->runtimeStateRepository->markRunSuccess('daily', ['note' => 'automation_disabled']);
			return;
		}

		$this->runtimeStateRepository->markRunStart('daily');

		try {
			$selection_summary = [];
			$game_informations = $this->fetchGameInformations('daily', $selection_summary);
			if (!$game_informations) {
				$this->runtimeStateRepository->markRunSuccess('daily', array_merge(['items' => 0], $selection_summary));
				return;
			}

			$wordpress_functions = new WordPressFunctions();
			$date                = new Date();
			$utility_factory     = new UtilityFactory();
			$market_target       = (new MarketTargetRepository())->getDefaultTarget();
			$daily_strategy      = new DailyPostStrategy($game_informations, $wordpress_functions, $date, $utility_factory, $market_target);
			(new Poster($daily_strategy))->post();
			$this->runtimeStateRepository->markRunSuccess('daily', array_merge(['items' => count($game_informations)], $selection_summary));
		} catch (Throwable $throwable) {
			$this->runtimeStateRepository->markRunFailure('daily', $throwable->getMessage());
			error_log('AGDC daily task failed: ' . $throwable->getMessage());
		}
	}

	/**
	 * Starts hourly post
	 *
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public function startHourlyPostTask()
	{
		if (!$this->isAutomationEnabled()) {
			$this->runtimeStateRepository->markRunSuccess('hourly', ['note' => 'automation_disabled']);
			return;
		}

		$this->runtimeStateRepository->markRunStart('hourly');

		try {
			$selection_summary = [];
			$game_informations   = $this->fetchGameInformations('hourly', $selection_summary);
			$wordpress_functions = new WordPressFunctions();
			$utility_factory     = new UtilityFactory();
			$market_target       = (new MarketTargetRepository())->getDefaultTarget();
			if ($game_informations) {
				foreach ($game_informations as $index => $game_information) {
					$free_games_post_strategy = new FreeGamesPostStrategy(
						$game_information,
						$utility_factory,
						$wordpress_functions,
						$market_target
					);
					(new Poster($free_games_post_strategy))->post();
				}
			}
			$this->runtimeStateRepository->markRunSuccess('hourly', array_merge(['items' => count($game_informations)], $selection_summary));
		} catch (Throwable $throwable) {
			$this->runtimeStateRepository->markRunFailure('hourly', $throwable->getMessage());
			error_log('AGDC hourly task failed: ' . $throwable->getMessage());
		}
	}

	public function setup()
	{
		$daily_post_time = (new SettingsRepository())->getAll()['posting']['daily_post_time'] ?? '06:00';
		$timestamp = strtotime($daily_post_time . ':00');
		if ($timestamp === false) {
			$timestamp = strtotime('06:00:00');
		}

		$this->wpFunctions->scheduleEvent('startScheduleHourlyPost', 'hourly', 'startHourlyPostTask');
		$this->wpFunctions->scheduleEvent('startDailyPostTask', 'daily', 'startDailyPostTask', $timestamp);
	}

	/**
	 * @return array
	 * @throws GuzzleException
	 */
	private function fetchGameInformations($fetchType = 'daily', array &$selectionSummary = []): array
	{
		$game_informations = (new Scraper($fetchType))->getOffers();
		if (!$game_informations) {
			$selectionSummary = [
				'found' => 0,
				'eligible' => 0,
				'already_posted' => 0,
				'duplicates_removed' => 0,
				'selected' => 0,
			];
			return [];
		}

		$database = new GameInformationDatabase();
		foreach ($game_informations as $index => $game_information) {
			$game_informations[$index] = $database->insertGameInformation($game_information);
		}

		if ($fetchType === 'hourly') {
			$selectionSummary = $this->offerSelectionService->summarizeHourlySelection($game_informations);
			return $this->offerSelectionService->selectForHourly($game_informations);
		}

		$selectionSummary = $this->offerSelectionService->summarizeDailySelection($game_informations);
		return $this->offerSelectionService->selectForDaily($game_informations);
	}

	private function isAutomationEnabled(): bool
	{
		return !empty((new SettingsRepository())->getAll()['general']['enabled']);
	}
}

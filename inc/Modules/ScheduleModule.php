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
	private const DAILY_MARKET_HOOK = 'agdc_run_daily_market';
	private const HOURLY_MARKET_HOOK = 'agdc_run_hourly_market';
	private const MARKET_QUEUE_INITIAL_DELAY_SECONDS = 90;
	private const DAILY_MARKET_STAGGER_SECONDS = 8 * MINUTE_IN_SECONDS;
	private const HOURLY_MARKET_STAGGER_SECONDS = 5 * MINUTE_IN_SECONDS;
	private const MANUAL_MARKET_PAUSE_MICROSECONDS = 1500000;

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
			$repo = new MarketTargetRepository();
			$targets = $repo->getRolloutTargets();

			if ($this->shouldQueueMarkets($targets)) {
				$queued = $this->queueMarketRuns(self::DAILY_MARKET_HOOK, $targets, self::DAILY_MARKET_STAGGER_SECONDS);
				$this->runtimeStateRepository->markRunSuccess(
					'daily',
					[
						'note' => 'queued_markets',
						'queued' => count($queued),
						'markets' => $queued,
					]
				);
				return;
			}

			$summary = $this->runDailyTargets($targets, true);
			$this->runtimeStateRepository->markRunSuccess('daily', $summary);
		} catch (Throwable $throwable) {
			$this->runtimeStateRepository->markRunFailure('daily', $throwable->getMessage());
			error_log('AGDC daily task failed: ' . $throwable->getMessage());
		}
	}

	public function runDailyMarketTask(string $marketKey): void
	{
		if ($marketKey === '') {
			return;
		}

		if (!$this->isAutomationEnabled()) {
			return;
		}

		try {
			$repo = new MarketTargetRepository();
			$target = $repo->findByKey($marketKey);
			if ($target === null) {
				return;
			}

			$summary = $this->runDailyTargets([$target], false);
			$this->runtimeStateRepository->markRunSuccess(
				'daily:' . $marketKey,
				$this->extractMarketRunMeta($summary, $marketKey)
			);
		} catch (Throwable $throwable) {
			$this->runtimeStateRepository->markRunFailure('daily:' . $marketKey, $throwable->getMessage());
			error_log('AGDC daily market task failed for ' . $marketKey . ': ' . $throwable->getMessage());
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
			$repo = new MarketTargetRepository();
			$targets = $repo->getRolloutTargets();

			if ($this->shouldQueueMarkets($targets)) {
				$queued = $this->queueMarketRuns(self::HOURLY_MARKET_HOOK, $targets, self::HOURLY_MARKET_STAGGER_SECONDS);
				$this->runtimeStateRepository->markRunSuccess(
					'hourly',
					[
						'note' => 'queued_markets',
						'queued' => count($queued),
						'markets' => $queued,
					]
				);
				return;
			}

			$summary = $this->runHourlyTargets($targets, true);
			$this->runtimeStateRepository->markRunSuccess('hourly', $summary);
		} catch (Throwable $throwable) {
			$this->runtimeStateRepository->markRunFailure('hourly', $throwable->getMessage());
			error_log('AGDC hourly task failed: ' . $throwable->getMessage());
		}
	}

	public function runHourlyMarketTask(string $marketKey): void
	{
		if ($marketKey === '') {
			return;
		}

		if (!$this->isAutomationEnabled()) {
			return;
		}

		try {
			$repo = new MarketTargetRepository();
			$target = $repo->findByKey($marketKey);
			if ($target === null) {
				return;
			}

			$summary = $this->runHourlyTargets([$target], false);
			$this->runtimeStateRepository->markRunSuccess(
				'hourly:' . $marketKey,
				$this->extractMarketRunMeta($summary, $marketKey)
			);
		} catch (Throwable $throwable) {
			$this->runtimeStateRepository->markRunFailure('hourly:' . $marketKey, $throwable->getMessage());
			error_log('AGDC hourly market task failed for ' . $marketKey . ': ' . $throwable->getMessage());
		}
	}

	public function setup()
	{
		$daily_post_time = (new SettingsRepository())->getAll()['posting']['daily_post_time'] ?? '06:00';
		$timestamp = strtotime($daily_post_time . ':00');
		if ($timestamp === false) {
			$timestamp = strtotime('06:00:00');
		}

		$this->wpFunctions->addHook(self::DAILY_MARKET_HOOK, 'runDailyMarketTask', 10, 1);
		$this->wpFunctions->addHook(self::HOURLY_MARKET_HOOK, 'runHourlyMarketTask', 10, 1);
		$this->wpFunctions->scheduleEvent('startScheduleHourlyPost', 'hourly', 'startHourlyPostTask');
		$this->wpFunctions->scheduleEvent('startDailyPostTask', 'daily', 'startDailyPostTask', $timestamp);
	}

	private function runDailyTargets(array $targets, bool $pauseBetweenMarkets): array
	{
		$summary = ['items' => 0, 'markets' => []];
		$wordpressFunctions = new WordPressFunctions();
		$date = new Date();
		$utilityFactory = new UtilityFactory();

		foreach ($targets as $index => $marketTarget) {
			$selectionSummary = [];
			$gameInformations = $this->fetchGameInformations('daily', $selectionSummary, $marketTarget);
			$summary['items'] += count($gameInformations);
			$summary['markets'][(string) ($marketTarget['market_key'] ?? 'unknown')] = array_merge(
				['items' => count($gameInformations)],
				$selectionSummary
			);

			if ($gameInformations !== []) {
				$dailyStrategy = new DailyPostStrategy($gameInformations, $wordpressFunctions, $date, $utilityFactory, $marketTarget);
				(new Poster($dailyStrategy))->post();
			}

			if ($pauseBetweenMarkets && $index < count($targets) - 1) {
				usleep(self::MANUAL_MARKET_PAUSE_MICROSECONDS);
			}
		}

		return $summary;
	}

	private function runHourlyTargets(array $targets, bool $pauseBetweenMarkets): array
	{
		$summary = ['items' => 0, 'markets' => []];
		$wordpressFunctions = new WordPressFunctions();
		$utilityFactory = new UtilityFactory();

		foreach ($targets as $index => $marketTarget) {
			$selectionSummary = [];
			$gameInformations = $this->fetchGameInformations('hourly', $selectionSummary, $marketTarget);
			$summary['items'] += count($gameInformations);
			$summary['markets'][(string) ($marketTarget['market_key'] ?? 'unknown')] = array_merge(
				['items' => count($gameInformations)],
				$selectionSummary
			);

			if ($gameInformations !== []) {
				foreach ($gameInformations as $gameInformation) {
					$freeGamesPostStrategy = new FreeGamesPostStrategy(
						$gameInformation,
						$utilityFactory,
						$wordpressFunctions,
						$marketTarget
					);
					(new Poster($freeGamesPostStrategy))->post();
				}
			}

			if ($pauseBetweenMarkets && $index < count($targets) - 1) {
				usleep(self::MANUAL_MARKET_PAUSE_MICROSECONDS);
			}
		}

		return $summary;
	}

	private function shouldQueueMarkets(array $targets): bool
	{
		return defined('DOING_CRON') && DOING_CRON && count($targets) > 1;
	}

	private function queueMarketRuns(string $hook, array $targets, int $staggerSeconds): array
	{
		$queued = [];
		$timestamp = time() + self::MARKET_QUEUE_INITIAL_DELAY_SECONDS;

		foreach ($targets as $index => $target) {
			$marketKey = (string) ($target['market_key'] ?? '');
			if ($marketKey === '') {
				continue;
			}

			$args = [$marketKey];
			if (!wp_next_scheduled($hook, $args)) {
				wp_schedule_single_event($timestamp + ($index * $staggerSeconds), $hook, $args);
			}

			$queued[] = $marketKey;
		}

		return $queued;
	}

	/**
	 * @return array
	 * @throws GuzzleException
	 */
	private function fetchGameInformations($fetchType = 'daily', array &$selectionSummary = [], ?array $marketTarget = null): array
	{
		$game_informations = (new Scraper($fetchType, $marketTarget))->getOffers();
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

		$database = new GameInformationDatabase($marketTarget);
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

	private function extractMarketRunMeta(array $summary, string $marketKey): array
	{
		$marketSummary = is_array($summary['markets'][$marketKey] ?? null) ? $summary['markets'][$marketKey] : [];

		return array_merge(
			[
				'market' => $marketKey,
				'items' => (int) ($marketSummary['items'] ?? ($summary['items'] ?? 0)),
			],
			$marketSummary
		);
	}
}

<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use AutoGamesDiscountCreator\Core\Settings\SettingsRepository;

class OfferSelectionService
{
	private GameTitleNormalizer $gameTitleNormalizer;
	private array $settings;

	public function __construct()
	{
		$this->gameTitleNormalizer = new GameTitleNormalizer();
		$this->settings = (new SettingsRepository())->getAll();
	}

	public function summarizeDailySelection(array $offers): array
	{
		$summary = [
			'found' => count($offers),
			'eligible' => 0,
			'already_posted' => 0,
			'duplicates_removed' => 0,
			'selected' => 0,
		];

		$offers = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => ((int) ($offer['offer_id'] ?? 0)) > 0
					&& ((float) ($offer['price'] ?? 0)) > 0
					&& empty($offer['is_free'])
			)
		);
		$summary['eligible'] = count($offers);

		if ($offers === []) {
			return $summary;
		}

		$marketTargetId = $this->extractMarketTargetId($offers);
		$posted_offer_ids = $this->getPostedOfferIds(array_column($offers, 'offer_id'), 'discount_roundup', $marketTargetId);
		$recently_posted_game_ids = $this->getRecentlyPostedGameIds(
			array_column($offers, 'game_id'),
			'discount_roundup',
			$marketTargetId,
			$this->getRepeatWindowDays('discount_roundup')
		);
		$summary['already_posted'] = count(
			array_filter(
				$offers,
				static fn(array $offer): bool => in_array((int) $offer['offer_id'], $posted_offer_ids, true)
					|| in_array((int) ($offer['game_id'] ?? 0), $recently_posted_game_ids, true)
			)
		);

		$remaining = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => !in_array((int) $offer['offer_id'], $posted_offer_ids, true)
					&& !in_array((int) ($offer['game_id'] ?? 0), $recently_posted_game_ids, true)
			)
		);

		$unique_keys = [];
		foreach ($remaining as $offer) {
			$unique_keys[$this->getGameGroupKey($offer)] = true;
		}

		$summary['selected'] = count($unique_keys);
		$summary['duplicates_removed'] = max(0, count($remaining) - $summary['selected']);

		return $summary;
	}

	public function selectForDaily(array $offers): array
	{
		$offers = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => ((int) ($offer['offer_id'] ?? 0)) > 0
					&& ((float) ($offer['price'] ?? 0)) > 0
					&& empty($offer['is_free'])
			)
		);

		if ($offers === []) {
			return [];
		}

		$marketTargetId = $this->extractMarketTargetId($offers);
		$posted_offer_ids = $this->getPostedOfferIds(array_column($offers, 'offer_id'), 'discount_roundup', $marketTargetId);
		$recently_posted_game_ids = $this->getRecentlyPostedGameIds(
			array_column($offers, 'game_id'),
			'discount_roundup',
			$marketTargetId,
			$this->getRepeatWindowDays('discount_roundup')
		);
		$offers = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => !in_array((int) $offer['offer_id'], $posted_offer_ids, true)
					&& !in_array((int) ($offer['game_id'] ?? 0), $recently_posted_game_ids, true)
			)
		);

		if ($offers === []) {
			return [];
		}

		$cheapest_by_game = [];
		foreach ($offers as $offer) {
			$group_key = $this->getGameGroupKey($offer);
			if (!isset($cheapest_by_game[$group_key])) {
				$cheapest_by_game[$group_key] = $offer;
				continue;
			}

			$current = $cheapest_by_game[$group_key];
			if ($this->isBetterDailyOffer($offer, $current)) {
				$cheapest_by_game[$group_key] = $offer;
			}
		}

		$selected = array_values($cheapest_by_game);
		usort(
			$selected,
			static function (array $left, array $right): int {
				$price_compare = ((float) ($right['price'] ?? 0)) <=> ((float) ($left['price'] ?? 0));
				if ($price_compare !== 0) {
					return $price_compare;
				}

				return ((float) ($right['cut'] ?? 0)) <=> ((float) ($left['cut'] ?? 0));
			}
		);

		return $selected;
	}

	public function summarizeHourlySelection(array $offers): array
	{
		$summary = [
			'found' => count($offers),
			'eligible' => 0,
			'already_posted' => 0,
			'duplicates_removed' => 0,
			'selected' => 0,
		];

		$offers = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => ((int) ($offer['offer_id'] ?? 0)) > 0
					&& (!empty($offer['is_free']) || ((float) ($offer['price'] ?? 0)) <= 0)
			)
		);
		$summary['eligible'] = count($offers);

		if ($offers === []) {
			return $summary;
		}

		$marketTargetId = $this->extractMarketTargetId($offers);
		$freeRepeatWindowDays = $this->getRepeatWindowDays('free_game');
		$posted_offer_ids = $freeRepeatWindowDays > 0
			? $this->getHourlyBlockedOfferIds(array_column($offers, 'offer_id'), $marketTargetId)
			: [];
		$recently_posted_game_ids = $freeRepeatWindowDays > 0
			? $this->getHourlyBlockedGameIds(array_column($offers, 'game_id'), $marketTargetId)
			: [];
		$summary['already_posted'] = count(
			array_filter(
				$offers,
				static fn(array $offer): bool => in_array((int) $offer['offer_id'], $posted_offer_ids, true)
					|| in_array((int) ($offer['game_id'] ?? 0), $recently_posted_game_ids, true)
			)
		);

		$remaining = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => !in_array((int) $offer['offer_id'], $posted_offer_ids, true)
					&& !in_array((int) ($offer['game_id'] ?? 0), $recently_posted_game_ids, true)
			)
		);

		$unique_keys = [];
		foreach ($remaining as $offer) {
			$unique_keys[$this->getGameGroupKey($offer)] = true;
		}

		$summary['selected'] = count($unique_keys);
		$summary['duplicates_removed'] = max(0, count($remaining) - count($unique_keys));

		return $summary;
	}

	public function selectForHourly(array $offers): array
	{
		$offers = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => ((int) ($offer['offer_id'] ?? 0)) > 0
					&& (!empty($offer['is_free']) || ((float) ($offer['price'] ?? 0)) <= 0)
			)
		);

		if ($offers === []) {
			return [];
		}

		$marketTargetId = $this->extractMarketTargetId($offers);
		$freeRepeatWindowDays = $this->getRepeatWindowDays('free_game');
		$posted_offer_ids = $freeRepeatWindowDays > 0
			? $this->getHourlyBlockedOfferIds(array_column($offers, 'offer_id'), $marketTargetId)
			: [];
		$recently_posted_game_ids = $freeRepeatWindowDays > 0
			? $this->getHourlyBlockedGameIds(array_column($offers, 'game_id'), $marketTargetId)
			: [];
		$seen_games = [];
		$selected = [];

		foreach ($offers as $offer) {
			$offer_id = (int) ($offer['offer_id'] ?? 0);
			if (in_array($offer_id, $posted_offer_ids, true)) {
				continue;
			}

			$gameId = (int) ($offer['game_id'] ?? 0);
			if ($gameId > 0 && in_array($gameId, $recently_posted_game_ids, true)) {
				continue;
			}

			$group_key = $this->getGameGroupKey($offer);
			if (isset($seen_games[$group_key])) {
				continue;
			}

			$seen_games[$group_key] = true;
			$selected[] = $offer;
		}

		return $selected;
	}

	private function getPostedOfferIds(array $offerIds, string $contentKind, ?int $marketTargetId = null): array
	{
		global $wpdb;

		$offer_ids = array_values(
			array_filter(
				array_map('intval', $offerIds),
				static fn(int $offer_id): bool => $offer_id > 0
			)
		);

		if ($offer_ids === []) {
			return [];
		}

		$placeholders = implode(', ', array_fill(0, count($offer_ids), '%d'));
		$table = $wpdb->prefix . 'agdc_generated_posts';
		if ($marketTargetId !== null && $marketTargetId > 0) {
			$query = $wpdb->prepare(
				"SELECT DISTINCT offer_id FROM {$table} WHERE content_kind = %s AND market_target_id = %d AND offer_id IN ({$placeholders})",
				array_merge([$contentKind, $marketTargetId], $offer_ids)
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT DISTINCT offer_id FROM {$table} WHERE content_kind = %s AND offer_id IN ({$placeholders})",
				array_merge([$contentKind], $offer_ids)
			);
		}

		$results = $wpdb->get_col($query);

		return array_map('intval', is_array($results) ? $results : []);
	}

	private function getPostedOfferIdsSince(array $offerIds, string $contentKind, ?int $marketTargetId, string $threshold): array
	{
		global $wpdb;

		$offer_ids = array_values(
			array_filter(
				array_map('intval', $offerIds),
				static fn(int $offer_id): bool => $offer_id > 0
			)
		);

		if ($offer_ids === []) {
			return [];
		}

		$placeholders = implode(', ', array_fill(0, count($offer_ids), '%d'));
		$table = $wpdb->prefix . 'agdc_generated_posts';

		if ($marketTargetId !== null && $marketTargetId > 0) {
			$query = $wpdb->prepare(
				"SELECT DISTINCT offer_id FROM {$table}
				WHERE content_kind = %s
					AND market_target_id = %d
					AND offer_id IN ({$placeholders})
					AND COALESCE(published_at, created_at) >= %s",
				array_merge([$contentKind, $marketTargetId], $offer_ids, [$threshold])
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT DISTINCT offer_id FROM {$table}
				WHERE content_kind = %s
					AND offer_id IN ({$placeholders})
					AND COALESCE(published_at, created_at) >= %s",
				array_merge([$contentKind], $offer_ids, [$threshold])
			);
		}

		$results = $wpdb->get_col($query);

		return array_map('intval', is_array($results) ? $results : []);
	}

	private function getRecentlyPostedGameIds(array $gameIds, string $contentKind, ?int $marketTargetId, int $windowDays): array
	{
		global $wpdb;

		if ($windowDays <= 0) {
			return [];
		}

		$gameIds = array_values(
			array_filter(
				array_map('intval', $gameIds),
				static fn(int $gameId): bool => $gameId > 0
			)
		);

		if ($gameIds === []) {
			return [];
		}

		$placeholders = implode(', ', array_fill(0, count($gameIds), '%d'));
		$table = $wpdb->prefix . 'agdc_generated_posts';
		$threshold = gmdate('Y-m-d H:i:s', time() - ($windowDays * DAY_IN_SECONDS));

		if ($marketTargetId !== null && $marketTargetId > 0) {
			$query = $wpdb->prepare(
				"SELECT DISTINCT game_id FROM {$table}
				WHERE content_kind = %s
					AND market_target_id = %d
					AND game_id IN ({$placeholders})
					AND COALESCE(published_at, created_at) >= %s",
				array_merge([$contentKind, $marketTargetId], $gameIds, [$threshold])
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT DISTINCT game_id FROM {$table}
				WHERE content_kind = %s
					AND game_id IN ({$placeholders})
					AND COALESCE(published_at, created_at) >= %s",
				array_merge([$contentKind], $gameIds, [$threshold])
			);
		}

		$results = $wpdb->get_col($query);

		return array_map('intval', is_array($results) ? $results : []);
	}

	private function getPostedGameIdsSince(array $gameIds, string $contentKind, ?int $marketTargetId, string $threshold): array
	{
		global $wpdb;

		$gameIds = array_values(
			array_filter(
				array_map('intval', $gameIds),
				static fn(int $gameId): bool => $gameId > 0
			)
		);

		if ($gameIds === []) {
			return [];
		}

		$placeholders = implode(', ', array_fill(0, count($gameIds), '%d'));
		$table = $wpdb->prefix . 'agdc_generated_posts';

		if ($marketTargetId !== null && $marketTargetId > 0) {
			$query = $wpdb->prepare(
				"SELECT DISTINCT game_id FROM {$table}
				WHERE content_kind = %s
					AND market_target_id = %d
					AND game_id IN ({$placeholders})
					AND COALESCE(published_at, created_at) >= %s",
				array_merge([$contentKind, $marketTargetId], $gameIds, [$threshold])
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT DISTINCT game_id FROM {$table}
				WHERE content_kind = %s
					AND game_id IN ({$placeholders})
					AND COALESCE(published_at, created_at) >= %s",
				array_merge([$contentKind], $gameIds, [$threshold])
			);
		}

		$results = $wpdb->get_col($query);

		return array_map('intval', is_array($results) ? $results : []);
	}

	private function getRepeatWindowDays(string $contentKind): int
	{
		$dataModel = is_array($this->settings['data_model'] ?? null) ? $this->settings['data_model'] : [];

		if ($contentKind === 'free_game') {
			return max(0, (int) ($dataModel['free_repeat_window_days'] ?? 7));
		}

		return max(0, (int) ($dataModel['daily_repeat_window_days'] ?? 7));
	}

	private function getHourlyBlockedOfferIds(array $offerIds, ?int $marketTargetId): array
	{
		return $this->getPostedOfferIdsSince(
			$offerIds,
			'free_game',
			$marketTargetId,
			$this->getFreeGameRepeatThreshold()
		);
	}

	private function getHourlyBlockedGameIds(array $gameIds, ?int $marketTargetId): array
	{
		return $this->getPostedGameIdsSince(
			$gameIds,
			'free_game',
			$marketTargetId,
			$this->getFreeGameRepeatThreshold()
		);
	}

	private function getFreeGameRepeatThreshold(): string
	{
		$windowDays = $this->getRepeatWindowDays('free_game');
		$currentTimestamp = current_time('timestamp');

		if ($windowDays <= 0) {
			return wp_date('Y-m-d 00:00:00', $currentTimestamp);
		}

		return wp_date('Y-m-d H:i:s', $currentTimestamp - ($windowDays * DAY_IN_SECONDS));
	}

	private function extractMarketTargetId(array $offers): ?int
	{
		foreach ($offers as $offer) {
			$marketTargetId = (int) ($offer['market_target_id'] ?? 0);
			if ($marketTargetId > 0) {
				return $marketTargetId;
			}
		}

		return null;
	}

	private function getGameGroupKey(array $offer): string
	{
		$normalizedTitle = $this->gameTitleNormalizer->normalize((string) ($offer['name'] ?? ''));
		if ($normalizedTitle !== '') {
			return 'name:' . sanitize_title($normalizedTitle);
		}

		$game_id = (int) ($offer['game_id'] ?? 0);
		if ($game_id > 0) {
			return 'game:' . $game_id;
		}

		return 'name:' . sanitize_title((string) ($offer['name'] ?? ''));
	}

	private function isBetterDailyOffer(array $candidate, array $current): bool
	{
		$candidate_price = (float) ($candidate['price'] ?? 0);
		$current_price = (float) ($current['price'] ?? 0);
		if ($candidate_price !== $current_price) {
			return $candidate_price < $current_price;
		}

		$candidate_cut = (float) ($candidate['cut'] ?? 0);
		$current_cut = (float) ($current['cut'] ?? 0);
		if ($candidate_cut !== $current_cut) {
			return $candidate_cut > $current_cut;
		}

		return ((int) ($candidate['offer_id'] ?? 0)) < ((int) ($current['offer_id'] ?? 0));
	}
}

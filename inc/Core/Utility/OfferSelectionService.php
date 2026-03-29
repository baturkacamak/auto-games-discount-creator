<?php

namespace AutoGamesDiscountCreator\Core\Utility;

class OfferSelectionService
{
	private GameTitleNormalizer $gameTitleNormalizer;

	public function __construct()
	{
		$this->gameTitleNormalizer = new GameTitleNormalizer();
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

		$posted_offer_ids = $this->getPostedOfferIds(array_column($offers, 'offer_id'), 'discount_roundup');
		$summary['already_posted'] = count(
			array_filter(
				$offers,
				static fn(array $offer): bool => in_array((int) $offer['offer_id'], $posted_offer_ids, true)
			)
		);

		$remaining = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => !in_array((int) $offer['offer_id'], $posted_offer_ids, true)
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

		$posted_offer_ids = $this->getPostedOfferIds(array_column($offers, 'offer_id'), 'discount_roundup');
		$offers = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => !in_array((int) $offer['offer_id'], $posted_offer_ids, true)
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

		$posted_offer_ids = $this->getPostedOfferIds(array_column($offers, 'offer_id'), 'free_game');
		$summary['already_posted'] = count(
			array_filter(
				$offers,
				static fn(array $offer): bool => in_array((int) $offer['offer_id'], $posted_offer_ids, true)
			)
		);

		$remaining = array_values(
			array_filter(
				$offers,
				static fn(array $offer): bool => !in_array((int) $offer['offer_id'], $posted_offer_ids, true)
			)
		);

		$unique_keys = [];
		foreach ($remaining as $offer) {
			$unique_keys[$this->getGameGroupKey($offer)] = true;
		}

		$summary['selected'] = count($remaining) > 0 ? 1 : 0;
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

		$posted_offer_ids = $this->getPostedOfferIds(array_column($offers, 'offer_id'), 'free_game');
		$seen_games = [];
		$selected = [];

		foreach ($offers as $offer) {
			$offer_id = (int) ($offer['offer_id'] ?? 0);
			if (in_array($offer_id, $posted_offer_ids, true)) {
				continue;
			}

			$group_key = $this->getGameGroupKey($offer);
			if (isset($seen_games[$group_key])) {
				continue;
			}

			$seen_games[$group_key] = true;
			$selected[] = $offer;
			break;
		}

		return $selected;
	}

	private function getPostedOfferIds(array $offerIds, string $contentKind): array
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
		$query = $wpdb->prepare(
			"SELECT DISTINCT offer_id FROM {$table} WHERE content_kind = %s AND offer_id IN ({$placeholders})",
			array_merge([$contentKind], $offer_ids)
		);

		$results = $wpdb->get_col($query);

		return array_map('intval', is_array($results) ? $results : []);
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

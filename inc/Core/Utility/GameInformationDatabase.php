<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 13/2/23
 * Time: 22:44
 */

namespace AutoGamesDiscountCreator\Core\Utility;

use AutoGamesDiscountCreator\Core\Settings\SettingsRepository;
use Exception;

class GameInformationDatabase
{
	private GameTitleNormalizer $gameTitleNormalizer;

	public function __construct()
	{
		$this->gameTitleNormalizer = new GameTitleNormalizer();
	}

	/**
	 * Inserts game information into the database.
	 *
	 * @param array $gameInfo An instance of the stdClass class.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function insertGameInformation(array $gameInfo): array
	{
		if ($this->isValidGameInformation($gameInfo)) {
			$game_record = $this->insertOrUpdateGame($gameInfo);
			$offer_record = $this->insertOrUpdateOffer($game_record, $gameInfo);
			$this->insertOfferSnapshot($offer_record, $gameInfo);

			$gameInfo['offer_id'] = (int) ($offer_record['id'] ?? 0);
			$gameInfo['game_id'] = (int) ($game_record['id'] ?? 0);

			return $gameInfo;
		}

		$gameInfo['offer_id'] = 0;
		$gameInfo['game_id'] = 0;

		return $gameInfo;
	}

	/**
	 * Check if the game information is valid.
	 *
	 * @param array $gameInfo An instance of the stdClass class.
	 *
	 * @return bool
	 */
	private function isValidGameInformation($gameInfo): bool
	{
		return isset($gameInfo['name']) && !empty($gameInfo['name']) && !empty($gameInfo['url']);
	}

	/**
	 * Inserts or updates the game information in the new agdc_games table.
	 *
	 * @throws Exception
	 */
	private function insertOrUpdateGame(array $gameInfo): array
	{
		global $wpdb;

		$table = $wpdb->prefix . 'agdc_games';
		$canonical_name = $this->gameTitleNormalizer->normalize((string) $gameInfo['name']);
		$normalized_name = sanitize_title($canonical_name);
		$source_url = (string) $gameInfo['url'];
		$existing_record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE normalized_name = %s LIMIT 1",
				$normalized_name
			),
			ARRAY_A
		);

		$data = [
			'canonical_name' => $canonical_name,
			'normalized_name' => $normalized_name,
			'slug' => $normalized_name,
			'source_key' => 'isthereanydeal',
			'source_url' => $source_url,
			'updated_at' => current_time('mysql'),
		];

		if ($existing_record) {
			$wpdb->update($table, $data, ['id' => $existing_record['id']]);

			return array_merge($existing_record, $data);
		}

		$wpdb->insert(
			$table,
			[
				'canonical_name' => $canonical_name,
				'normalized_name' => $normalized_name,
				'slug' => $normalized_name,
				'source_key' => 'isthereanydeal',
				'source_url' => $source_url,
				'created_at' => current_time('mysql'),
				'updated_at' => current_time('mysql'),
			]
		);

		$data['id'] = (int) $wpdb->insert_id;

		return $data;
	}

	/**
	 * Inserts or updates the current offer state for the game.
	 *
	 * @throws Exception
	 */
	private function insertOrUpdateOffer(array $gameRecord, array $gameInfo): array
	{
		global $wpdb;

		$table = $wpdb->prefix . 'agdc_offers';
		$settings = (new SettingsRepository())->getAll();
		$market_target = $this->resolveMarketTarget((string) ($settings['data_model']['default_market_target_key'] ?? 'tr-tr'));
		$store_key = $this->resolveStoreKey($gameInfo, $settings);
		$store_id = $this->resolveStoreId($store_key);
		$is_free = $this->isFreeOffer($gameInfo);
		$offer_type = $is_free ? 'free_game' : 'discount';
		$external_offer_id = (string) ($gameInfo['external_offer_id'] ?? '');
		$currency_code = (string) ($gameInfo['currency_code'] ?? $market_target['default_currency_code'] ?? 'USD');
		$region_code = (string) ($gameInfo['region_code'] ?? $market_target['country_code'] ?? '');
		$language_code = (string) ($gameInfo['language_code'] ?? $market_target['language_code'] ?? '');
		$sale_price = isset($gameInfo['price']) ? (float) $gameInfo['price'] : 0.0;
		$discount_percent = isset($gameInfo['cut']) ? (float) preg_replace('/[^0-9.]/', '', (string) $gameInfo['cut']) : 0.0;
		$regular_price = $this->calculateRegularPrice($sale_price, $discount_percent, $is_free);
		$offer_fingerprint = md5(
			implode('|', [$gameRecord['id'], $store_key, $region_code, $currency_code, $offer_type, $external_offer_id !== '' ? $external_offer_id : $gameInfo['url']])
		);

		$existing_record = null;
		if ($external_offer_id !== '') {
			$existing_record = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE game_id = %d AND store_id = %d AND external_offer_id = %s LIMIT 1",
					(int) $gameRecord['id'],
					$store_id,
					$external_offer_id
				),
				ARRAY_A
			);
		}

		if (!$existing_record) {
			$existing_record = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE game_id = %d AND store_id = %d AND deeplink_url = %s LIMIT 1",
					(int) $gameRecord['id'],
					$store_id,
					(string) ($gameInfo['url'] ?? '')
				),
				ARRAY_A
			);
		}

		if (!$existing_record) {
			$existing_record = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM {$table} WHERE offer_fingerprint = %s LIMIT 1", $offer_fingerprint),
				ARRAY_A
			);
		}

		$data = [
			'game_id' => (int) $gameRecord['id'],
			'store_id' => $store_id,
			'market_target_id' => isset($market_target['id']) ? (int) $market_target['id'] : null,
			'source_key' => 'isthereanydeal',
			'external_offer_id' => $external_offer_id !== '' ? $external_offer_id : null,
			'offer_fingerprint' => $offer_fingerprint,
			'offer_type' => $offer_type,
			'availability_status' => 'active',
			'region_code' => $region_code ?: null,
			'currency_code' => $currency_code,
			'language_code' => $language_code ?: null,
			'regular_price_amount' => $regular_price,
			'sale_price_amount' => $sale_price,
			'discount_percent' => $discount_percent,
			'is_free' => $is_free ? 1 : 0,
			'deeplink_url' => (string) $gameInfo['url'],
			'last_seen_at' => current_time('mysql'),
			'updated_at' => current_time('mysql'),
		];

		if ($existing_record) {
			$wpdb->update($table, $data, ['id' => $existing_record['id']]);

			return array_merge($existing_record, $data);
		}

		$insert_data = array_merge(
			$data,
			[
				'created_at' => current_time('mysql'),
			]
		);
		$wpdb->insert($table, $insert_data);
		$data['id'] = (int) $wpdb->insert_id;

		return $data;
	}

	/**
	 * Stores the historical snapshot of the offer fetched from the source.
	 *
	 * @throws Exception
	 */
	private function insertOfferSnapshot(array $offerRecord, array $gameInfo): void
	{
		global $wpdb;

		$table = $wpdb->prefix . 'agdc_offer_snapshots';
		$payload = wp_json_encode($gameInfo);
		$sale_price = isset($gameInfo['price']) ? (float) $gameInfo['price'] : 0.0;
		$discount_percent = isset($gameInfo['cut']) ? (float) preg_replace('/[^0-9.]/', '', (string) $gameInfo['cut']) : 0.0;
		$is_free = $this->isFreeOffer($gameInfo);
		$snapshot_hash = md5(
			implode('|', [$offerRecord['id'], $sale_price, $discount_percent, $payload])
		);

		$exists = $wpdb->get_var(
			$wpdb->prepare("SELECT id FROM {$table} WHERE snapshot_hash = %s LIMIT 1", $snapshot_hash)
		);

		if ($exists) {
			return;
		}

		$wpdb->insert(
			$table,
			[
				'offer_id' => (int) $offerRecord['id'],
				'snapshot_hash' => $snapshot_hash,
				'availability_status' => 'active',
				'currency_code' => (string) ($offerRecord['currency_code'] ?? 'USD'),
				'regular_price_amount' => $offerRecord['regular_price_amount'],
				'sale_price_amount' => $offerRecord['sale_price_amount'],
				'discount_percent' => $offerRecord['discount_percent'],
				'is_free' => $is_free ? 1 : 0,
				'payload' => $payload,
				'fetched_at' => current_time('mysql'),
			]
		);
	}

	private function resolveStoreKey(array $gameInfo, array $settings): string
	{
		$url = strtolower((string) ($gameInfo['url'] ?? ''));

		$map = [
			'steampowered' => 'steam',
			'steam' => 'steam',
			'epicgames' => 'epic',
			'gog' => 'gog',
			'humblebundle' => 'humble',
			'humblestore' => 'humble',
			'bundlestars' => 'fanatical',
			'fanatical' => 'fanatical',
		];

		foreach ($map as $needle => $store_key) {
			if ($url !== '' && str_contains($url, $needle)) {
				return $store_key;
			}
		}

		if ($this->isFreeOffer($gameInfo)) {
			return (string) ($settings['data_model']['default_free_store_key'] ?? 'epic');
		}

		return (string) ($settings['data_model']['default_discount_store_key'] ?? 'steam');
	}

	private function resolveStoreId(string $storeKey): int
	{
		global $wpdb;

		$table = $wpdb->prefix . 'agdc_stores';
		$store_id = $wpdb->get_var(
			$wpdb->prepare("SELECT id FROM {$table} WHERE store_key = %s LIMIT 1", $storeKey)
		);

		return $store_id ? (int) $store_id : 0;
	}

	private function resolveMarketTarget(string $marketKey): array
	{
		global $wpdb;

		$table = $wpdb->prefix . 'agdc_market_targets';
		$target = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE market_key = %s LIMIT 1", $marketKey),
			ARRAY_A
		);

		return is_array($target) ? $target : [];
	}

	private function isFreeOffer(array $gameInfo): bool
	{
		$price = isset($gameInfo['price']) ? (float) $gameInfo['price'] : 0.0;
		return $price <= 0.0;
	}

	private function calculateRegularPrice(float $salePrice, float $discountPercent, bool $isFree): ?float
	{
		if ($isFree) {
			return null;
		}

		if ($discountPercent <= 0 || $discountPercent >= 100) {
			return $salePrice;
		}

		return round($salePrice / (1 - ($discountPercent / 100)), 2);
	}
}

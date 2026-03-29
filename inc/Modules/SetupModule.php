<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 15:23
 */

namespace AutoGamesDiscountCreator\Modules;

use AutoGamesDiscountCreator\Core\Module\AbstractModule;

class SetupModule extends AbstractModule
{
	public function setup()
	{
		$this->maybeUpgradeSchema();
	}

	private function maybeUpgradeSchema(): void
	{
		if (get_option(AGDC_SCHEMA_VERSION_OPTION) === AGDC_SCHEMA_VERSION) {
			return;
		}

		$this->createLegacyTables();
		$this->createNewSchema();
		$this->seedStores();
		$this->seedMarketTargets();

		update_option(AGDC_SCHEMA_VERSION_OPTION, AGDC_SCHEMA_VERSION, false);
	}

	private function createLegacyTables(): void
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$legacy_prefix = $wpdb->prefix . 'game_scraper_';

		dbDelta(
			"CREATE TABLE {$legacy_prefix}games (
				ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(150) NOT NULL,
				url varchar(150) NOT NULL,
				status char(1) NOT NULL DEFAULT '1',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (ID),
				KEY name (name)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$legacy_prefix}prices (
				ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				game_id bigint(20) unsigned NOT NULL,
				price decimal(10,2) NOT NULL DEFAULT 0.00,
				region varchar(5) NOT NULL DEFAULT 'TR',
				cut int(3) NOT NULL DEFAULT 0,
				status char(1) NOT NULL DEFAULT '1',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (ID),
				KEY game_id (game_id),
				KEY region (region)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$legacy_prefix}rambouillet_posts (
				ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				price_id bigint(20) unsigned NOT NULL,
				status_wordpress char(1) NOT NULL DEFAULT '0',
				status char(1) NOT NULL DEFAULT '1',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (ID),
				KEY price_id (price_id)
			) {$charset_collate};"
		);
	}

	private function createNewSchema(): void
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix = $wpdb->prefix . 'agdc_';

		dbDelta(
			"CREATE TABLE {$prefix}stores (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				store_key varchar(64) NOT NULL,
				store_name varchar(191) NOT NULL,
				homepage_url varchar(255) DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY store_key (store_key)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}market_targets (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				market_key varchar(64) NOT NULL,
				country_code varchar(8) NOT NULL,
				language_code varchar(12) NOT NULL,
				default_currency_code varchar(8) NOT NULL,
				site_section varchar(128) DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY market_key (market_key),
				KEY country_language (country_code, language_code)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}games (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				source_key varchar(64) DEFAULT NULL,
				external_game_id varchar(128) DEFAULT NULL,
				canonical_name varchar(191) NOT NULL,
				normalized_name varchar(191) NOT NULL,
				slug varchar(191) DEFAULT NULL,
				developer_name varchar(191) DEFAULT NULL,
				publisher_name varchar(191) DEFAULT NULL,
				primary_genre varchar(100) DEFAULT NULL,
				artwork_url varchar(255) DEFAULT NULL,
				source_url varchar(255) DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY normalized_name (normalized_name),
				KEY source_lookup (source_key, external_game_id)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}offers (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				game_id bigint(20) unsigned NOT NULL,
				store_id bigint(20) unsigned NOT NULL,
				market_target_id bigint(20) unsigned DEFAULT NULL,
				source_key varchar(64) NOT NULL,
				external_offer_id varchar(128) DEFAULT NULL,
				offer_fingerprint varchar(191) NOT NULL,
				offer_type varchar(32) NOT NULL DEFAULT 'discount',
				availability_status varchar(32) NOT NULL DEFAULT 'active',
				region_code varchar(8) DEFAULT NULL,
				currency_code varchar(8) NOT NULL,
				language_code varchar(12) DEFAULT NULL,
				regular_price_amount decimal(12,2) DEFAULT NULL,
				sale_price_amount decimal(12,2) DEFAULT NULL,
				discount_percent decimal(5,2) DEFAULT NULL,
				is_free tinyint(1) NOT NULL DEFAULT 0,
				deeplink_url varchar(255) DEFAULT NULL,
				starts_at datetime DEFAULT NULL,
				expires_at datetime DEFAULT NULL,
				last_seen_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY offer_fingerprint (offer_fingerprint),
				KEY game_store (game_id, store_id),
				KEY market_target_id (market_target_id),
				KEY offer_type (offer_type),
				KEY is_free (is_free)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}offer_snapshots (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				offer_id bigint(20) unsigned NOT NULL,
				snapshot_hash varchar(191) NOT NULL,
				availability_status varchar(32) NOT NULL DEFAULT 'active',
				currency_code varchar(8) NOT NULL,
				regular_price_amount decimal(12,2) DEFAULT NULL,
				sale_price_amount decimal(12,2) DEFAULT NULL,
				discount_percent decimal(5,2) DEFAULT NULL,
				is_free tinyint(1) NOT NULL DEFAULT 0,
				payload longtext DEFAULT NULL,
				fetched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY snapshot_hash (snapshot_hash),
				KEY offer_id (offer_id),
				KEY fetched_at (fetched_at)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}generated_posts (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				wp_post_id bigint(20) unsigned DEFAULT NULL,
				game_id bigint(20) unsigned DEFAULT NULL,
				offer_id bigint(20) unsigned DEFAULT NULL,
				market_target_id bigint(20) unsigned DEFAULT NULL,
				content_kind varchar(32) NOT NULL DEFAULT 'discount_roundup',
				language_code varchar(12) DEFAULT NULL,
				post_status varchar(32) NOT NULL DEFAULT 'draft',
				published_at datetime DEFAULT NULL,
				source_snapshot_at datetime DEFAULT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY wp_post_id (wp_post_id),
				KEY offer_id (offer_id),
				KEY market_target_id (market_target_id),
				KEY content_kind (content_kind)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$prefix}runs (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				run_type varchar(32) NOT NULL,
				source_key varchar(64) DEFAULT NULL,
				market_target_id bigint(20) unsigned DEFAULT NULL,
				status varchar(32) NOT NULL,
				item_count int(11) NOT NULL DEFAULT 0,
				error_message text DEFAULT NULL,
				started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				finished_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY run_type (run_type),
				KEY status (status),
				KEY market_target_id (market_target_id)
			) {$charset_collate};"
		);
	}

	private function seedStores(): void
	{
		global $wpdb;

		$table = $wpdb->prefix . 'agdc_stores';
		$stores = [
			['store_key' => 'steam', 'store_name' => 'Steam', 'homepage_url' => 'https://store.steampowered.com'],
			['store_key' => 'epic', 'store_name' => 'Epic Games Store', 'homepage_url' => 'https://store.epicgames.com'],
			['store_key' => 'gog', 'store_name' => 'GOG', 'homepage_url' => 'https://www.gog.com'],
			['store_key' => 'humble', 'store_name' => 'Humble Store', 'homepage_url' => 'https://www.humblebundle.com/store'],
			['store_key' => 'fanatical', 'store_name' => 'Fanatical', 'homepage_url' => 'https://www.fanatical.com'],
		];

		foreach ($stores as $store) {
			$wpdb->replace(
				$table,
				$store,
				['%s', '%s', '%s']
			);
		}
	}

	private function seedMarketTargets(): void
	{
		global $wpdb;

		$table = $wpdb->prefix . 'agdc_market_targets';
		$targets = [
			[
				'market_key' => 'tr-tr',
				'country_code' => 'TR',
				'language_code' => 'tr',
				'default_currency_code' => 'TRY',
				'site_section' => 'tr',
			],
			[
				'market_key' => 'ro-ro',
				'country_code' => 'RO',
				'language_code' => 'ro',
				'default_currency_code' => 'RON',
				'site_section' => 'ro',
			],
			[
				'market_key' => 'es-es',
				'country_code' => 'ES',
				'language_code' => 'es',
				'default_currency_code' => 'EUR',
				'site_section' => 'es',
			],
			[
				'market_key' => 'us-en',
				'country_code' => 'US',
				'language_code' => 'en',
				'default_currency_code' => 'USD',
				'site_section' => 'en-us',
			],
			[
				'market_key' => 'gb-en',
				'country_code' => 'GB',
				'language_code' => 'en',
				'default_currency_code' => 'GBP',
				'site_section' => 'en-gb',
			],
			[
				'market_key' => 'de-de',
				'country_code' => 'DE',
				'language_code' => 'de',
				'default_currency_code' => 'EUR',
				'site_section' => 'de',
			],
			[
				'market_key' => 'fr-fr',
				'country_code' => 'FR',
				'language_code' => 'fr',
				'default_currency_code' => 'EUR',
				'site_section' => 'fr',
			],
			[
				'market_key' => 'global-en',
				'country_code' => 'US',
				'language_code' => 'en',
				'default_currency_code' => 'USD',
				'site_section' => 'global',
			],
		];

		foreach ($targets as $target) {
			$wpdb->replace(
				$table,
				$target,
				['%s', '%s', '%s', '%s', '%s']
			);
		}
	}
}

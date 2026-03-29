<?php

namespace AutoGamesDiscountCreator\Core\Settings;

use AutoGamesDiscountCreator\Core\Utility\JsonParser;
use Exception;

class SettingsRepository
{
	private array $defaults;

	public function __construct()
	{
		$this->defaults = $this->loadDefaults();
	}

	public function getAll(): array
	{
		$stored_settings = [];

		if (function_exists('get_option')) {
			$stored_settings = get_option(AGDC_SETTINGS_OPTION, []);
		}

		if (!is_array($stored_settings)) {
			$stored_settings = [];
		}

		return $this->mergeDefaults($stored_settings, $this->defaults);
	}

	public function getDefaults(): array
	{
		return $this->defaults;
	}

	private function loadDefaults(): array
	{
		$json_settings = [];

		try {
			$json_settings = (new JsonParser(AGDC_SETTINGS_FILE))->parse();
		} catch (Exception $exception) {
			$json_settings = [];
		}

		return $this->mergeDefaults(
			$json_settings,
			[
				'general' => [
					'enabled' => true,
					'dry_run' => true,
				],
				'posting' => [
					'author_id' => 1,
					'category_id' => 1,
					'post_status' => 'draft',
					'tags' => 'oyun indirimleri, game deals, uciki',
					'daily_post_time' => '06:00',
				],
				'posting_daily' => [
					'author_id' => 1,
					'category_id' => 1,
					'post_status' => 'publish',
					'tags' => 'oyun indirimleri, game deals, steam indirimleri, ucuz oyunlar, uciki',
				],
				'posting_free' => [
					'author_id' => 1,
					'category_id' => 14,
					'post_status' => 'publish',
					'tags' => 'ucretsiz oyunlar, free games, epic games, gog deals, uciki',
				],
				'data_model' => [
					'default_market_target_key' => 'tr-tr',
					'default_discount_store_key' => 'steam',
					'default_free_store_key' => 'epic',
				],
				'source' => [
					'itad_session_token' => '',
					'itad_session_cookie' => '',
					'itad_visitor_cookie' => '',
					'itad_country_code' => 'TR',
					'itad_currency_code' => 'TRY',
					'daily_payloads' => [],
					'hourly_payloads' => [],
				],
				'queries' => [],
			]
		);
	}

	private function mergeDefaults(array $settings, array $defaults): array
	{
		foreach ($defaults as $key => $default_value) {
			if (!array_key_exists($key, $settings)) {
				$settings[$key] = $default_value;
				continue;
			}

			if (is_array($default_value) && is_array($settings[$key]) && $this->isAssociative($default_value)) {
				$settings[$key] = $this->mergeDefaults($settings[$key], $default_value);
			}
		}

		return $settings;
	}

	private function isAssociative(array $value): bool
	{
		return array_keys($value) !== range(0, count($value) - 1);
	}
}

<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use AutoGamesDiscountCreator\AutoGamesDiscountCreator;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Throwable;

if (!class_exists('AutoGamesDiscountCreator\Core\Utility\Scraper')) {
	class Scraper
	{
		private const SESSION_TRANSIENT_KEY = 'agdc_itad_session_bootstrap';

		private array $settings;
		private WordPressFunctions $wpFunctions;
		private WebClient $guzzle;
		private string $type;
		private array $resolvedUrlCache = [];
		private GameTitleNormalizer $gameTitleNormalizer;

		public const BASE_URL = 'https://isthereanydeal.com';
		private const LIST_ENDPOINT = 'deals/api/list/';
		private const GAMES_ENDPOINT = 'deals/api/games/';
		private const PRICES_ENDPOINT = 'deals/api/prices/';

		public function __construct(string $type = 'daily')
		{
			$this->type        = $type;
			$this->settings    = AutoGamesDiscountCreator::getInstance()->settings;
			$this->wpFunctions = new WordPressFunctions($this);
			$this->gameTitleNormalizer = new GameTitleNormalizer();
			$this->setGuzzle();
		}

		public function getOffers(): array
		{
			$offers = [];
			$payloads = $this->getPayloadsForType();
			$transient_time = $this->type === 'hourly' ? HOUR_IN_SECONDS : DAY_IN_SECONDS / 4;

			foreach ($payloads as $index => $payload) {
				$query_key = (string) ($payload['query-key'] ?? ($this->type . '_payload_' . $index));
				$transient_key = 'agdc_source_' . $query_key;
				$transient_data = $this->wpFunctions->getTransient($transient_key);

				if (!$transient_data) {
					$transient_data = $this->fetchPayloadOffers($payload);
					$this->wpFunctions->setTransient($transient_key, $transient_data, $transient_time);
				}

				if (is_array($transient_data)) {
					$offers = array_merge($offers, $transient_data);
				}
			}

			return $offers;
		}

		private function setGuzzle(): void
		{
			$source_settings = $this->hydrateSourceSettingsFromTransient($this->settings['source'] ?? []);
			if ($this->shouldBootstrapSession($source_settings)) {
				$source_settings = $this->bootstrapAnonymousSession($source_settings);
			}

			$this->settings['source'] = $source_settings;
			$this->configureClient($source_settings);
		}

		private function configureClient(array $source_settings): void
		{
			$session_token = (string) ($source_settings['itad_session_token'] ?? '');
			$session_cookie = (string) ($source_settings['itad_session_cookie'] ?? $session_token);
			$visitor_cookie = (string) ($source_settings['itad_visitor_cookie'] ?? '');
			$country_code = (string) ($source_settings['itad_country_code'] ?? 'TR');
			$currency_code = (string) ($source_settings['itad_currency_code'] ?? 'TRY');

			$cookies = ['country' => $country_code];
			if ($visitor_cookie !== '') {
				$cookies['visitor'] = $visitor_cookie;
			}
			if ($session_cookie !== '') {
				$cookies['sess2'] = $session_cookie;
			}

			$cookie_jar = CookieJar::fromArray($cookies, parse_url(self::BASE_URL, PHP_URL_HOST));

			$headers = [
				'Accept' => 'application/json',
				'Accept-Language' => 'en-US,en;q=0.9,tr;q=0.8',
				'Content-Type' => 'application/json',
				'Origin' => self::BASE_URL,
				'Priority' => 'u=1, i',
				'Referer' => self::BASE_URL . '/browse/',
				'Sec-Fetch-Dest' => 'empty',
				'Sec-Fetch-Mode' => 'cors',
				'Sec-Fetch-Site' => 'same-origin',
				'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0',
			];

			if ($session_token !== '') {
				$headers['itad-sessiontoken'] = $session_token;
			}

			$this->guzzle = new WebClient(
				[
					'base_uri' => self::BASE_URL . '/',
					'cookies'  => $cookie_jar,
					'headers'  => $headers,
				]
			);
		}

		private function shouldBootstrapSession(array $source_settings): bool
		{
			return (string) ($source_settings['itad_session_token'] ?? '') === ''
				|| (string) ($source_settings['itad_session_cookie'] ?? '') === ''
				|| (string) ($source_settings['itad_visitor_cookie'] ?? '') === '';
		}

		private function hydrateSourceSettingsFromTransient(array $source_settings): array
		{
			$cached = $this->wpFunctions->getTransient(self::SESSION_TRANSIENT_KEY);
			if (!is_array($cached)) {
				return $source_settings;
			}

			foreach (['itad_session_token', 'itad_session_cookie', 'itad_visitor_cookie', 'itad_country_code', 'itad_currency_code'] as $key) {
				if (($source_settings[$key] ?? '') === '' && ($cached[$key] ?? '') !== '') {
					$source_settings[$key] = $cached[$key];
				}
			}

			return $source_settings;
		}

		private function bootstrapAnonymousSession(array $source_settings): array
		{
			$temp_client = new WebClient(
				[
					'base_uri' => self::BASE_URL . '/',
					'cookies' => new CookieJar(),
					'headers' => [
						'Accept-Language' => 'en-US,en;q=0.9,tr;q=0.8',
						'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0',
					],
				]
			);

			$response = $temp_client->getClient()->request('GET', 'browse/');
			$html = $response->getBody()->getContents();
			$token = $this->extractSessionTokenFromHtml($html);
			if ($token === '') {
				return $source_settings;
			}

			$host = parse_url(self::BASE_URL, PHP_URL_HOST);
			$cookie_jar = $temp_client->getClient()->getConfig('cookies');
			$visitor_cookie = '';
			$session_cookie = '';
			$country_cookie = '';

			if ($cookie_jar instanceof CookieJar) {
				foreach ($cookie_jar->toArray() as $cookie) {
					if (($cookie['Domain'] ?? '') !== $host) {
						continue;
					}

					if (($cookie['Name'] ?? '') === 'visitor') {
						$visitor_cookie = (string) ($cookie['Value'] ?? '');
					}
					if (($cookie['Name'] ?? '') === 'sess2') {
						$session_cookie = (string) ($cookie['Value'] ?? '');
					}
					if (($cookie['Name'] ?? '') === 'country') {
						$country_cookie = (string) ($cookie['Value'] ?? '');
					}
				}
			}

			$bootstrapped = array_merge(
				$source_settings,
				[
					'itad_session_token' => $token,
					'itad_session_cookie' => $session_cookie !== '' ? $session_cookie : $token,
					'itad_visitor_cookie' => $visitor_cookie,
					'itad_country_code' => $country_cookie !== '' ? $country_cookie : (string) ($source_settings['itad_country_code'] ?? 'TR'),
				]
			);

			$this->wpFunctions->setTransient(self::SESSION_TRANSIENT_KEY, $bootstrapped, 6 * HOUR_IN_SECONDS);

			return $bootstrapped;
		}

		private function extractSessionTokenFromHtml(string $html): string
		{
			if (preg_match('/"token":"([^"]+)"/', $html, $matches)) {
				return (string) ($matches[1] ?? '');
			}

			return '';
		}

		private function postJsonWithSessionRetry(string $endpoint, array $payload): array
		{
			try {
				$response = $this->guzzle->postJson($endpoint, $payload);
			} catch (Throwable $throwable) {
				if (!$this->shouldRetryWithFreshSession($throwable)) {
					throw $throwable;
				}

				$this->settings['source'] = $this->bootstrapAnonymousSession($this->settings['source'] ?? []);
				$this->configureClient($this->settings['source']);
				$response = $this->guzzle->postJson($endpoint, $payload);
			}

			$decoded = json_decode($response->getBody()->getContents(), true);
			return is_array($decoded) ? $decoded : [];
		}

		private function shouldRetryWithFreshSession(Throwable $throwable): bool
		{
			$message = $throwable->getMessage();
			return str_contains($message, '400 Bad Request') || str_contains($message, '401');
		}

		private function getPayloadsForType(): array
		{
			$key = $this->type === 'hourly' ? 'hourly_payloads' : 'daily_payloads';
			$payloads = $this->settings['source'][$key] ?? [];

			return is_array($payloads) ? $payloads : [];
		}

		private function fetchPayloadOffers(array $payload): array
		{
			$gids = $this->resolveGidsForPayload($payload);
			if ($gids === []) {
				return [];
			}

			try {
				$games_response = $this->fetchGamesResponse($gids);
				$prices_response = $this->fetchPricesResponse($payload, $gids);

				return $this->normalizeOffers($gids, $games_response, $prices_response);
			} catch (Throwable $throwable) {
				error_log('AGDC scraper payload failed: ' . $throwable->getMessage());
				return [];
			}
		}

		private function resolveGidsForPayload(array $payload): array
		{
			$gids = array_values(
				array_filter(
					$payload['gids'] ?? [],
					static fn($gid): bool => is_string($gid) && $gid !== ''
				)
			);

			if ($gids !== []) {
				return array_values(array_unique($gids));
			}

			return $this->discoverGidsFromListEndpoint($payload);
		}

		private function discoverGidsFromListEndpoint(array $payload): array
		{
			$all_gids = [];
			$pages = max(1, (int) ($payload['pages'] ?? 1));
			$limit = isset($payload['limit']) ? max(1, min(100, (int) $payload['limit'])) : null;

			for ($page = 0; $page < $pages; $page++) {
				$offset = $limit === null ? 0 : $page * $limit;
				$list_response = $this->fetchListResponse($payload, $offset, $limit);
				$page_gids = $this->extractGidsFromListResponse($list_response);

				if ($page_gids === []) {
					break;
				}

				$all_gids = array_merge($all_gids, $page_gids);

				if ($limit === null || count($page_gids) < $limit) {
					break;
				}
			}

			return array_values(array_unique($all_gids));
		}

		private function fetchListResponse(array $payload, int $offset, ?int $limit): array
		{
			$request_payload = [
				'offset' => $offset,
				'sort' => $payload['sort'] ?? null,
				'filter' => $payload['filter'] ?? new \stdClass(),
				'ignored' => !empty($payload['ignored']),
				'mature' => !empty($payload['mature']),
				'nondeals' => !empty($payload['nondeals']),
			];
			if ($limit !== null) {
				$request_payload['limit'] = $limit;
			}

			return $this->postJsonWithSessionRetry(self::LIST_ENDPOINT, $request_payload);
		}

		private function fetchGamesResponse(array $gids): array
		{
			return $this->postJsonWithSessionRetry(self::GAMES_ENDPOINT, ['gids' => $gids]);
		}

		private function extractGidsFromListResponse(array $data): array
		{
			$source = $data['data'] ?? $data;
			if (!is_array($source)) {
				return [];
			}

			$gids = [];

			foreach ($source as $item) {
				if (is_string($item) && $item !== '') {
					$gids[] = $item;
					continue;
				}

				if (!is_array($item)) {
					continue;
				}

				$gid = $item['gid'] ?? $item['id'] ?? null;
				if (is_string($gid) && $gid !== '') {
					$gids[] = $gid;
				}
			}

			return $gids;
		}

		private function fetchPricesResponse(array $payload, array $gids): array
		{
			$request_payload = [
				'gids' => array_values($gids),
				'filter' => $payload['filter'] ?? new \stdClass(),
			];

			return $this->postJsonWithSessionRetry(self::PRICES_ENDPOINT, $request_payload);
		}

		private function normalizeOffers(array $gids, array $gamesResponse, array $pricesResponse): array
		{
			$gamesIndex = $this->indexGamesByGid($gamesResponse);
			$offers = [];

			foreach ($gids as $gid) {
				$game_meta = $gamesIndex[$gid] ?? ['gid' => $gid];
				$price_meta = $this->extractPriceMetaForGid($pricesResponse, $gid);
				$offer = $this->buildNormalizedOffer($gid, $game_meta, $price_meta);
				if ($offer) {
					$offers[] = $offer;
				}
			}

			return $offers;
		}

		private function indexGamesByGid(array $data): array
		{
			$index = [];

			foreach ($data as $item) {
				if (!is_array($item) || count($item) < 2 || !is_string($item[0]) || !is_array($item[1])) {
					continue;
				}

				$game = $item[1];
				$title = $game['title'] ?? $game['name'] ?? null;
				if (!is_string($title) || $title === '') {
					continue;
				}

				$index[$item[0]] = [
					'gid' => $item[0],
					'title' => $title,
					'slug' => (string) ($game['slug'] ?? sanitize_title($title)),
					'url' => (string) ($game['url'] ?? ''),
					'assets' => is_array($game['assets'] ?? null) ? $game['assets'] : [],
				];
			}

			$this->walkData(
				$data,
				function (array $node) use (&$index): void {
					$gid = $node['gid'] ?? $node['id'] ?? null;
					$title = $node['title'] ?? $node['name'] ?? null;
					if (!is_string($gid) || !is_string($title) || $title === '') {
						return;
					}

					$index[$gid] = [
						'gid' => $gid,
						'title' => $title,
						'slug' => (string) ($node['slug'] ?? sanitize_title($title)),
						'url' => (string) ($node['url'] ?? $node['shopUrl'] ?? ''),
						'assets' => is_array($node['assets'] ?? null) ? $node['assets'] : [],
					];
				}
			);

			return $index;
		}

		private function extractPriceMetaForGid(array $data, string $gid): array
		{
			foreach ($data as $item) {
				if (!is_array($item) || count($item) < 2 || $item[0] !== $gid || !is_array($item[1])) {
					continue;
				}

				$offers = $item[1];
				if ($offers === []) {
					return [];
				}

				$first_offer = $offers[0] ?? [];
				return is_array($first_offer) ? $first_offer : [];
			}

			if (isset($data[$gid]) && is_array($data[$gid])) {
				return $data[$gid];
			}

			$matches = [];
			$this->walkData(
				$data,
				function (array $node) use ($gid, &$matches): void {
					$node_gid = $node['gid'] ?? $node['gameId'] ?? $node['id'] ?? null;
					if ($node_gid === $gid || (($node['game'] ?? null) === $gid)) {
						$matches[] = $node;
					}
				}
			);

			return $matches[0] ?? [];
		}

		private function buildNormalizedOffer(string $gid, array $gameMeta, array $priceMeta): ?array
		{
			$name = (string) ($gameMeta['title'] ?? $priceMeta['title'] ?? '');
			$name = $this->gameTitleNormalizer->normalize($name);
			if ($name === '') {
				return null;
			}

			$url = (string) (
				$priceMeta['url']
				?? $priceMeta['shopUrl']
				?? $gameMeta['url']
				?? ''
			);
			$url = $this->resolveOfferUrl($url, $priceMeta);

			$store_key = $this->normalizeStoreKey(
				(string) ($priceMeta['shop']['id'] ?? $priceMeta['shop'] ?? $priceMeta['shopKey'] ?? $priceMeta['store'] ?? '')
			);
			$price = $this->extractMoneyAmount($priceMeta['priceNew'] ?? $priceMeta['price'] ?? $priceMeta['currentPrice'] ?? $priceMeta['amount'] ?? null);
			$regular_price = $this->extractMoneyAmount($priceMeta['priceOld'] ?? $priceMeta['priceRegular'] ?? $priceMeta['basePrice'] ?? $priceMeta['regularAmount'] ?? null);
			$cut = $this->extractFloat($priceMeta, ['cut', 'discount', 'discountPercent']);
			$currency_code = (string) ($this->extractCurrencyCode($priceMeta['priceNew'] ?? null) ?? $priceMeta['currency'] ?? $priceMeta['currencyCode'] ?? $this->settings['source']['itad_currency_code'] ?? 'USD');
			$region_code = (string) ($priceMeta['country'] ?? $priceMeta['region'] ?? $this->settings['source']['itad_country_code'] ?? '');
			$language_code = (string) ($this->resolveLanguageCodeForCurrentSettings());
			$is_free = $price <= 0.0;

			if ($cut <= 0 && $regular_price > 0 && $price > 0 && $regular_price > $price) {
				$cut = round((1 - ($price / $regular_price)) * 100, 2);
			}

			$thumbnail_url = $this->resolveThumbnailUrl($gameMeta, $priceMeta);

			return [
				'gid' => $gid,
				'itad_slug' => (string) ($gameMeta['slug'] ?? ''),
				'external_offer_id' => (string) ($priceMeta['productId'] ?? ''),
				'name' => $name,
				'url' => $url,
				'price' => $price,
				'regular_price' => $regular_price > 0 ? $regular_price : null,
				'cut' => $cut,
				'store_key' => $store_key,
				'currency_code' => strtoupper($currency_code),
				'region_code' => strtoupper($region_code),
				'language_code' => $language_code,
				'is_free' => $is_free,
				'offer_type' => $is_free ? 'free_game' : 'discount',
				'thumbnail_url' => $thumbnail_url,
				'offer_id' => 0,
			];
		}

		private function resolveLanguageCodeForCurrentSettings(): string
		{
			$market_key = (string) ($this->settings['data_model']['default_market_target_key'] ?? 'tr-tr');
			$parts = explode('-', $market_key);
			return $parts[1] ?? 'tr';
		}

		private function resolveThumbnailUrl(array $gameMeta, array $priceMeta): string
		{
			$assets = $gameMeta['assets'] ?? [];
			if (is_array($assets)) {
				foreach (['banner300', 'banner', 'boxart', 'banner145'] as $asset_key) {
					if (!empty($assets[$asset_key])) {
						return (string) $assets[$asset_key];
					}
				}
			}

			foreach (['image', 'thumb', 'thumbnail', 'banner'] as $key) {
				if (!empty($priceMeta[$key])) {
					return (string) $priceMeta[$key];
				}
			}

			return '';
		}

		private function resolveOfferUrl(string $url, array $priceMeta): string
		{
			$url = trim($url);
			if ($url === '') {
				return '';
			}

			if (!str_contains($url, 'itad.link/')) {
				return $url;
			}

			$product_id = (string) ($priceMeta['productId'] ?? '');
			$cache_key = $product_id !== '' ? $product_id : md5($url);

			if (isset($this->resolvedUrlCache[$cache_key])) {
				return $this->resolvedUrlCache[$cache_key];
			}

			$transient_key = 'agdc_offer_url_' . substr(md5($cache_key), 0, 24);
			$cached_url = $this->wpFunctions->getTransient($transient_key);
			if (is_string($cached_url) && $cached_url !== '') {
				$this->resolvedUrlCache[$cache_key] = $cached_url;
				return $cached_url;
			}

			try {
				$resolved_url = $this->guzzle->resolveFinalUrl($url);
			} catch (Throwable $throwable) {
				$resolved_url = $url;
			}

			if ($resolved_url === '') {
				$resolved_url = $url;
			}

			$this->resolvedUrlCache[$cache_key] = $resolved_url;
			$this->wpFunctions->setTransient($transient_key, $resolved_url, 7 * DAY_IN_SECONDS);

			return $resolved_url;
		}

		private function normalizeStoreKey(string $raw): string
		{
			$raw = strtolower(trim($raw));
			$map = [
				'61' => 'steam',
				'16' => 'epic',
				'35' => 'gog',
				'37' => 'humble',
				'6' => 'fanatical',
				'steam' => 'steam',
				'epic game store' => 'epic',
				'epic' => 'epic',
				'gog' => 'gog',
				'humble store' => 'humble',
				'humblestore' => 'humble',
				'fanatical' => 'fanatical',
				'bundlestars' => 'fanatical',
			];

			return $map[$raw] ?? $raw;
		}

		private function extractMoneyAmount($value): float
		{
			if (is_array($value) && isset($value[0]) && is_numeric($value[0])) {
				return round(((float) $value[0]) / 100, 2);
			}

			if (is_numeric($value)) {
				return (float) $value;
			}

			return 0.0;
		}

		private function extractCurrencyCode($value): ?string
		{
			if (is_array($value) && isset($value[1]) && is_string($value[1]) && $value[1] !== '') {
				return strtoupper($value[1]);
			}

			return null;
		}

		private function extractFloat(array $source, array $keys): float
		{
			foreach ($keys as $key) {
				if (array_key_exists($key, $source) && is_numeric($source[$key])) {
					return (float) $source[$key];
				}
			}

			foreach ($source as $value) {
				if (is_array($value)) {
					$nested = $this->extractFloat($value, $keys);
					if ($nested !== 0.0) {
						return $nested;
					}
				}
			}

			return 0.0;
		}

		private function walkData($data, callable $callback): void
		{
			if (!is_array($data)) {
				return;
			}

			$callback($data);

			foreach ($data as $value) {
				if (is_array($value)) {
					$this->walkData($value, $callback);
				}
			}
		}
	}
}

<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image;

use AutoGamesDiscountCreator\AutoGamesDiscountCreator;
use AutoGamesDiscountCreator\Core\Utility\WebClient;

class EpicCatalogImageLookup
{
	private const CACHE_PREFIX = 'agdc_epic_free_catalog_';
	private const MISS_OPTION = 'agdc_epic_image_misses';
	private const ENDPOINT = 'https://store-site-backend-static.ak.epicgames.com/freeGamesPromotions';

	private WebClient $webClient;
	private ImageUrlNormalizer $normalizer;
	private string $countryCode;
	private string $locale;

	public function __construct(WebClient $webClient, ImageUrlNormalizer $normalizer, string $countryCode = 'US', string $locale = 'en-US')
	{
		$this->webClient = $webClient;
		$this->normalizer = $normalizer;
		$this->countryCode = strtoupper($countryCode);
		$this->locale = $locale;
	}

	public function findImageUrlForSlug(string $slug): ?string
	{
		$slug = trim($slug);
		if ($slug === '') {
			return null;
		}

		$catalog = $this->getCatalogIndex();
		$imageUrl = $catalog[$slug] ?? null;
		if (is_string($imageUrl) && $imageUrl !== '') {
			$this->clearMiss($slug);
			return $imageUrl;
		}

		$this->recordMiss($slug);
		return null;
	}

	private function getCatalogIndex(): array
	{
		$cacheKey = self::CACHE_PREFIX . strtolower($this->countryCode) . '_' . strtolower(str_replace('-', '_', $this->locale));
		if (function_exists('get_transient')) {
			$cached = get_transient($cacheKey);
			if (is_array($cached)) {
				return $cached;
			}
		}

		$url = add_query_arg(
			[
				'locale' => $this->locale,
				'country' => $this->countryCode,
				'allowCountries' => $this->countryCode,
			],
			self::ENDPOINT
		);

		$raw = $this->webClient->get($url);
		$decoded = json_decode($raw, true);
		$elements = $decoded['data']['Catalog']['searchStore']['elements'] ?? [];
		$index = $this->buildIndex(is_array($elements) ? $elements : []);

		if (function_exists('set_transient')) {
			set_transient($cacheKey, $index, HOUR_IN_SECONDS);
		}

		return $index;
	}

	private function buildIndex(array $elements): array
	{
		$index = [];

		foreach ($elements as $element) {
			if (!is_array($element)) {
				continue;
			}

			$imageUrl = $this->extractPreferredImageUrl($element['keyImages'] ?? []);
			if ($imageUrl === null) {
				continue;
			}

			foreach ($this->extractCandidateSlugs($element) as $slug) {
				$index[$slug] = $imageUrl;
			}
		}

		return $index;
	}

	private function extractPreferredImageUrl($keyImages): ?string
	{
		if (!is_array($keyImages)) {
			return null;
		}

		$grouped = [];
		foreach ($keyImages as $image) {
			if (!is_array($image)) {
				continue;
			}

			$type = (string) ($image['type'] ?? '');
			$url = $this->normalizer->normalize((string) ($image['url'] ?? ''));
			if ($type === '' || $url === null) {
				continue;
			}

			$grouped[$type][] = $url;
		}

		foreach (['OgImage', 'OfferImageWide', 'featuredMedia', 'Thumbnail', 'OfferImageTall'] as $preferredType) {
			if (!empty($grouped[$preferredType][0])) {
				return $grouped[$preferredType][0];
			}
		}

		return null;
	}

	private function extractCandidateSlugs(array $element): array
	{
		$slugs = [];

		foreach ((array) ($element['offerMappings'] ?? []) as $mapping) {
			if (is_array($mapping) && !empty($mapping['pageSlug'])) {
				$slugs[] = (string) $mapping['pageSlug'];
			}
		}

		foreach ((array) ($element['catalogNs']['mappings'] ?? []) as $mapping) {
			if (is_array($mapping) && !empty($mapping['pageSlug'])) {
				$slugs[] = (string) $mapping['pageSlug'];
			}
		}

		foreach (['productSlug', 'urlSlug'] as $field) {
			if (!empty($element[$field])) {
				$slugs[] = (string) $element[$field];
			}
		}

		foreach ((array) ($element['customAttributes'] ?? []) as $attribute) {
			if (!is_array($attribute) || ($attribute['key'] ?? '') !== 'com.epicgames.app.productSlug' || empty($attribute['value'])) {
				continue;
			}

			$slugs[] = (string) $attribute['value'];
		}

		$normalized = [];
		foreach ($slugs as $slug) {
			$slug = trim($slug, '/');
			if ($slug === '') {
				continue;
			}

			$normalized[] = $slug;
			if (str_contains($slug, '/')) {
				$normalized[] = trim((string) strtok($slug, '/'));
			}
		}

		return array_values(array_unique($normalized));
	}

	private function recordMiss(string $slug): void
	{
		if (!function_exists('get_option') || !function_exists('update_option') || !function_exists('current_time')) {
			return;
		}

		$misses = get_option(self::MISS_OPTION, []);
		if (!is_array($misses)) {
			$misses = [];
		}

		$misses[$slug] = [
			'last_seen_at' => current_time('mysql'),
			'country' => $this->countryCode,
			'locale' => $this->locale,
		];

		update_option(self::MISS_OPTION, $misses, false);
	}

	private function clearMiss(string $slug): void
	{
		if (!function_exists('get_option') || !function_exists('update_option')) {
			return;
		}

		$misses = get_option(self::MISS_OPTION, []);
		if (!is_array($misses) || !isset($misses[$slug])) {
			return;
		}

		unset($misses[$slug]);
		update_option(self::MISS_OPTION, $misses, false);
	}
}

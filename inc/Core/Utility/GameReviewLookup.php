<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use Throwable;

class GameReviewLookup
{
	private const CACHE_PREFIX = 'agdc_itad_reviews_';
	private WebClient $webClient;

	public function __construct(WebClient $webClient)
	{
		$this->webClient = $webClient;
	}

	public function lookupBySlug(string $slug): array
	{
		$slug = trim($slug);
		if ($slug === '') {
			return [];
		}

		$cacheKey = self::CACHE_PREFIX . substr(md5($slug), 0, 24);
		$cached = function_exists('get_transient') ? get_transient($cacheKey) : false;
		if (is_array($cached)) {
			return $cached;
		}

		$data = [];

		try {
			$html = $this->webClient->get('https://isthereanydeal.com/game/' . rawurlencode($slug) . '/info/');
			$data = $this->extractReviewData($html);
		} catch (Throwable $throwable) {
			$data = [];
		}

		if (function_exists('set_transient')) {
			set_transient($cacheKey, $data, 7 * DAY_IN_SECONDS);
		}

		return $data;
	}

	private function extractReviewData(string $html): array
	{
		if ($html === '') {
			return [];
		}

		$startNeedle = 'var page = ';
		$endNeedle = "\nvar sentry";
		$start = strpos($html, $startNeedle);
		if ($start === false) {
			return [];
		}

		$start += strlen($startNeedle);
		$end = strpos($html, $endNeedle, $start);
		if ($end === false || $end <= $start) {
			return [];
		}

		$json = trim(substr($html, $start, $end - $start));
		$json = rtrim($json, ';');

		$payload = json_decode($json, true);
		if (!is_array($payload)) {
			return [];
		}

		$reviews = $payload[1]['game']['detail']['reviews'] ?? null;
		if (is_array($reviews)) {
			$normalized = $this->normalizeReviews($reviews);
			if ($normalized !== []) {
				return $normalized;
			}
		}

		return $this->extractReviewDataFromHtml($html);
	}

	private function extractReviewDataFromHtml(string $html): array
	{
		if ($html === '') {
			return [];
		}

		$normalized = [];
		if (!preg_match_all('/<a href="[^"]*" class="svelte-lul36k" target="_blank">(.*?)<\/a>/s', $html, $matches)) {
			return [];
		}

		foreach ($matches[1] as $cardHtml) {
			if (!is_string($cardHtml) || $cardHtml === '') {
				continue;
			}

			if (!preg_match('/labels__positive[^>]*>.*?(\d+)%/s', $cardHtml, $positiveMatch)) {
				continue;
			}

			if (!preg_match('/<div class="source[^"]*">([^<]+)<\/div>/s', $cardHtml, $sourceMatch)) {
				continue;
			}

			$source = trim(html_entity_decode((string) ($sourceMatch[1] ?? ''), ENT_QUOTES | ENT_HTML5));
			$positive = (float) ($positiveMatch[1] ?? 0);
			$count = null;
			if (preg_match('/<div class="counts[^"]*">([^<]+)<\/div>/s', $cardHtml, $countMatch)) {
				$countString = preg_replace('/[^\d]/', '', (string) ($countMatch[1] ?? ''));
				$count = $countString !== '' ? (int) $countString : null;
			}

			$this->assignReviewMetric($normalized, $source, $positive, $count);
		}

		return $normalized;
	}

	private function normalizeReviews(array $reviews): array
	{
		$normalized = [];

		foreach ($reviews as $review) {
			if (!is_array($review)) {
				continue;
			}

			$source = (string) ($review['source'] ?? '');
			$positive = isset($review['positive']) && is_numeric($review['positive'])
				? (float) $review['positive']
				: null;
			$count = isset($review['count']) && is_numeric($review['count'])
				? (int) $review['count']
				: null;

			if ($positive === null) {
				continue;
			}

			$this->assignReviewMetric($normalized, $source, $positive, $count);
		}

		return $normalized;
	}

	private function assignReviewMetric(array &$normalized, string $source, float $positive, ?int $count): void
	{
		switch ($source) {
			case 'Steam':
				$normalized['steam_rating'] = $positive;
				$normalized['steam_review_count'] = $count;
				break;
			case 'OpenCritic':
				$normalized['opencritic_score'] = $positive;
				$normalized['opencritic_review_count'] = $count;
				break;
			case 'Metascore':
				$normalized['meta_score'] = $positive;
				$normalized['meta_review_count'] = $count;
				break;
			case 'Metacritic User Score':
				$normalized['user_score'] = $positive;
				$normalized['user_review_count'] = $count;
				break;
		}
	}
}

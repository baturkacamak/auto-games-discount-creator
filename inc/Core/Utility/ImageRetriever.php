<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Class ImageRetriever
 *
 * This class is responsible for retrieving the remote images from a given URL.
 *
 * @package AutoGamesDiscountCreator\Core\Utility
 */
class ImageRetriever
{
	private const CACHE_PREFIX = 'agdc_store_image_';
	private const CACHE_MISS_MARKER = '__agdc_store_image_miss__';

	/**
	 * @var WebClient
	 */
	private $webClient;

	/**
	 * @var DOMHandler
	 */
	private $domHandler;

	/**
	 * ImageRetriever constructor.
	 *
	 * Initializes the WebClient and DOMNodeHandler instances.
	 */
	public function __construct(WebClient $webClient, DOMHandler $domHandler)
	{
		$this->webClient  = $webClient;
		$this->domHandler = $domHandler;
	}

	/**
	 * Retrieves the remote image from the given URL.
	 *
	 * @param string $url The URL to retrieve the remote image from.
	 *
	 * @return string The URL of the remote image.
	 */
	public function retrieve(string $url, string $fallback = ''): string
	{
		$url = trim($url);
		if ($url === '') {
			return $fallback;
		}

		$cache_key = self::CACHE_PREFIX . substr(md5($url), 0, 24);
		if (function_exists('get_transient')) {
			$cached = get_transient($cache_key);
			if ($cached === self::CACHE_MISS_MARKER) {
				return $fallback;
			}

			if (is_string($cached) && $cached !== '') {
				return $cached;
			}
		}

		$html_response = '';
		try {
			$html_response = $this->webClient->getUntilMatch(
				$url,
				'~<meta[^>]+property=["\']og:image["\'][^>]+content=["\'][^"\']+["\']|<meta[^>]+content=["\'][^"\']+["\'][^>]+property=["\']og:image["\']~i'
			);

			if ($html_response === '') {
				$html_response = $this->webClient->get($url);
			}
		} catch (GuzzleException $e) {
		}

		if ($html_response) {
			$image_url = $this->extractOpenGraphImage($html_response);
			if ($image_url !== '') {
				if (function_exists('set_transient')) {
					set_transient($cache_key, $image_url, 7 * DAY_IN_SECONDS);
				}
				return $image_url;
			}

			$xpath = $this->domHandler->createXpathFromHtml($html_response);
			$meta_image_node = $xpath->query('//meta[@property="og:image"]');
			if ($meta_image_node && $meta_image_node->length) {
				$image_url = $meta_image_node->item(0)->getAttribute('content') ? $meta_image_node->item(0)->getAttribute(
					'content'
				) : '';
				if ($image_url !== '' && function_exists('set_transient')) {
					set_transient($cache_key, $image_url, 7 * DAY_IN_SECONDS);
				}
				return $image_url !== '' ? $image_url : $fallback;
			}
		}

		if (function_exists('set_transient')) {
			set_transient($cache_key, self::CACHE_MISS_MARKER, 6 * HOUR_IN_SECONDS);
		}

		return $fallback;
	}

	private function extractOpenGraphImage(string $html): string
	{
		$patterns = [
			'~<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']~i',
			'~<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']~i',
		];

		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $html, $matches) === 1) {
				return trim(html_entity_decode((string) ($matches[1] ?? ''), ENT_QUOTES | ENT_HTML5));
			}
		}

		return '';
	}
}

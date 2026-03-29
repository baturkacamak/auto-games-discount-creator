<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

class ExistingStoreCdnImageStrategy implements ImageSourceStrategyInterface
{
	public function resolve(array $context): ?string
	{
		$thumbnail_url = trim(html_entity_decode((string) ($context['thumbnail_url'] ?? ''), ENT_QUOTES | ENT_HTML5));
		if ($thumbnail_url === '') {
			return null;
		}

		$host = strtolower((string) parse_url($thumbnail_url, PHP_URL_HOST));
		if ($host === '') {
			return null;
		}

		$allowed_hosts = [
			'images.gog-statics.com',
			'www.gog.com',
			'shared.fastly.steamstatic.com',
			'cdn.cloudflare.steamstatic.com',
			'steamcdn-a.akamaihd.net',
			'hb.imgix.net',
			'cdn-ext.fanatical.com',
			'media.fanatical.com',
			'static-assets-prod.epicgames.com',
			'cdn1.epicgames.com',
			'images.ctfassets.net',
		];

		if (!in_array($host, $allowed_hosts, true)) {
			return null;
		}

		$parts = parse_url($thumbnail_url);
		if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
			return $thumbnail_url;
		}

		$normalized = $parts['scheme'] . '://' . $parts['host'];
		if (!empty($parts['path'])) {
			$normalized .= $parts['path'];
		}

		if (str_contains($host, 'imgix.net') && !empty($parts['query'])) {
			parse_str($parts['query'], $query);
			$filtered = array_intersect_key(
				$query,
				array_flip(['auto', 'fit', 'h', 'w', 'q', 'fm'])
			);
			if ($filtered !== []) {
				$normalized .= '?' . http_build_query($filtered);
			}
		}

		return $normalized;
	}
}

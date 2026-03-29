<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image;

class ImageUrlNormalizer
{
	public function normalize(string $url, array $allowedQueryKeys = []): ?string
	{
		$url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
		if ($url === '') {
			return null;
		}

		$parts = parse_url($url);
		if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
			return null;
		}

		$normalized = $parts['scheme'] . '://' . $parts['host'];
		if (!empty($parts['path'])) {
			$normalized .= $parts['path'];
		}

		if ($allowedQueryKeys !== [] && !empty($parts['query'])) {
			parse_str($parts['query'], $query);
			$filtered = array_intersect_key($query, array_flip($allowedQueryKeys));
			if ($filtered !== []) {
				$normalized .= '?' . http_build_query($filtered);
			}
		}

		return $normalized;
	}
}

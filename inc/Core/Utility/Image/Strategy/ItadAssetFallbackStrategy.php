<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

class ItadAssetFallbackStrategy implements ImageSourceStrategyInterface
{
	public function resolve(array $context): ?string
	{
		$thumbnail_url = trim((string) ($context['thumbnail_url'] ?? ''));
		if ($thumbnail_url === '') {
			return null;
		}

		$host = strtolower((string) parse_url($thumbnail_url, PHP_URL_HOST));
		if ($host === '' || !str_contains($host, 'isthereanydeal.com')) {
			return null;
		}

		return $thumbnail_url;
	}
}

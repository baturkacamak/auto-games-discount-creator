<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

class HumbleAgeCheckImageStrategy implements ImageSourceStrategyInterface
{
	public function resolve(array $context): ?string
	{
		$store_key = (string) ($context['store_key'] ?? '');
		$url = (string) ($context['url'] ?? '');
		if ($store_key !== 'humble' || $url === '') {
			return null;
		}

		$parts = parse_url($url);
		$host = strtolower((string) ($parts['host'] ?? ''));
		$path = (string) ($parts['path'] ?? '');
		if ($host !== 'www.humblebundle.com') {
			return null;
		}

		if (preg_match('#^/store/agecheck/([^/?#]+)#', $path, $matches)) {
			return 'https://www.humblebundle.com/store/' . $matches[1];
		}

		return null;
	}
}

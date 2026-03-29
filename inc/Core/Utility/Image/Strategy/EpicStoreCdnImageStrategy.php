<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

use AutoGamesDiscountCreator\Core\Utility\Image\ImageUrlNormalizer;

class EpicStoreCdnImageStrategy implements ImageSourceStrategyInterface
{
	private ImageUrlNormalizer $normalizer;

	public function __construct(ImageUrlNormalizer $normalizer)
	{
		$this->normalizer = $normalizer;
	}

	public function resolve(array $context): ?string
	{
		if ((string) ($context['store_key'] ?? '') !== 'epic') {
			return null;
		}

		$thumbnail_url = (string) ($context['thumbnail_url'] ?? '');
		$host = strtolower((string) parse_url($thumbnail_url, PHP_URL_HOST));
		if (!in_array($host, ['static-assets-prod.epicgames.com', 'cdn1.epicgames.com', 'images.ctfassets.net'], true)) {
			return null;
		}

		return $this->normalizer->normalize($thumbnail_url);
	}
}

<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

use AutoGamesDiscountCreator\Core\Utility\Image\ImageUrlNormalizer;

class HumbleStoreCdnImageStrategy implements ImageSourceStrategyInterface
{
	private ImageUrlNormalizer $normalizer;

	public function __construct(ImageUrlNormalizer $normalizer)
	{
		$this->normalizer = $normalizer;
	}

	public function resolve(array $context): ?string
	{
		if ((string) ($context['store_key'] ?? '') !== 'humble') {
			return null;
		}

		$thumbnail_url = (string) ($context['thumbnail_url'] ?? '');
		$host = strtolower((string) parse_url($thumbnail_url, PHP_URL_HOST));
		if ($host !== 'hb.imgix.net') {
			return null;
		}

		return $this->normalizer->normalize($thumbnail_url, ['auto', 'fit', 'h', 'w', 'q', 'fm']);
	}
}

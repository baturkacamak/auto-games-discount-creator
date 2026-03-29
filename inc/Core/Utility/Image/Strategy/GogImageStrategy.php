<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

use AutoGamesDiscountCreator\Core\Utility\Image\ImageUrlNormalizer;

class GogImageStrategy implements ImageSourceStrategyInterface
{
	private ImageUrlNormalizer $normalizer;

	public function __construct(ImageUrlNormalizer $normalizer)
	{
		$this->normalizer = $normalizer;
	}

	public function resolve(array $context): ?string
	{
		if ((string) ($context['store_key'] ?? '') !== 'gog') {
			return null;
		}

		$thumbnail_url = (string) ($context['thumbnail_url'] ?? '');
		$host = strtolower((string) parse_url($thumbnail_url, PHP_URL_HOST));
		if (!in_array($host, ['images.gog-statics.com', 'www.gog.com'], true)) {
			return null;
		}

		return $this->normalizer->normalize($thumbnail_url);
	}
}

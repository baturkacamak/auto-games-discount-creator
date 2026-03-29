<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

use AutoGamesDiscountCreator\Core\Utility\Image\ImageUrlNormalizer;

class FanaticalImageStrategy implements ImageSourceStrategyInterface
{
	private ImageUrlNormalizer $normalizer;

	public function __construct(ImageUrlNormalizer $normalizer)
	{
		$this->normalizer = $normalizer;
	}

	public function resolve(array $context): ?string
	{
		if ((string) ($context['store_key'] ?? '') !== 'fanatical') {
			return null;
		}

		$thumbnail_url = (string) ($context['thumbnail_url'] ?? '');
		$host = strtolower((string) parse_url($thumbnail_url, PHP_URL_HOST));
		if (!in_array($host, ['cdn-ext.fanatical.com', 'media.fanatical.com'], true)) {
			return null;
		}

		return $this->normalizer->normalize($thumbnail_url);
	}
}

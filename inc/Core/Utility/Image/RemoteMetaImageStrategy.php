<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image;

use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\ImageSourceStrategyInterface;
use AutoGamesDiscountCreator\Core\Utility\ImageRetriever;

class RemoteMetaImageStrategy implements ImageSourceStrategyInterface
{
	private ImageRetriever $imageRetriever;

	public function __construct(ImageRetriever $imageRetriever)
	{
		$this->imageRetriever = $imageRetriever;
	}

	public function resolve(array $context): ?string
	{
		if ((string) ($context['store_key'] ?? '') === 'epic') {
			return null;
		}

		$url = (string) ($context['url'] ?? '');
		if ($url === '') {
			return null;
		}

		$fallback = (string) ($context['thumbnail_url'] ?? '');
		$image_url = $this->imageRetriever->retrieve($url, $fallback);

		return $image_url !== '' ? $image_url : null;
	}
}

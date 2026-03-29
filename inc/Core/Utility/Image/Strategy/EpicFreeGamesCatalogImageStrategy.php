<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

use AutoGamesDiscountCreator\Core\Utility\Image\EpicCatalogImageLookup;

class EpicFreeGamesCatalogImageStrategy implements ImageSourceStrategyInterface
{
	/**
	 * @var EpicCatalogImageLookup[]
	 */
	private array $lookups;

	public function __construct(array $lookups)
	{
		$this->lookups = $lookups;
	}

	public function resolve(array $context): ?string
	{
		if ((string) ($context['store_key'] ?? '') !== 'epic') {
			return null;
		}

		$url = (string) ($context['url'] ?? '');
		if (!preg_match('~/store/p/([^/?#]+)~i', $url, $matches)) {
			return null;
		}

		$slug = (string) ($matches[1] ?? '');
		foreach ($this->lookups as $lookup) {
			if (!$lookup instanceof EpicCatalogImageLookup) {
				continue;
			}

			$imageUrl = $lookup->findImageUrlForSlug($slug);
			if (is_string($imageUrl) && $imageUrl !== '') {
				return $imageUrl;
			}
		}

		return null;
	}
}

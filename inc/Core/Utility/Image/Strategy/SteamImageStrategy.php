<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

class SteamImageStrategy implements ImageSourceStrategyInterface
{
	public function resolve(array $context): ?string
	{
		$store_key = (string) ($context['store_key'] ?? '');
		$url = (string) ($context['url'] ?? '');
		if ($store_key !== 'steam' || $url === '') {
			return null;
		}

		if (!preg_match('#store\.steampowered\.com/app/(\d+)#i', $url, $matches)) {
			return null;
		}

		return sprintf(
			'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/%s/capsule_616x353.jpg',
			$matches[1]
		);
	}
}

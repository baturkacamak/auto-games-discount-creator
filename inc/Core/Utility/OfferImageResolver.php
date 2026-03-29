<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\ImageSourceStrategyInterface;

class OfferImageResolver
{
	/**
	 * @var ImageSourceStrategyInterface[]
	 */
	private array $strategies;

	/**
	 * @param ImageSourceStrategyInterface[] $strategies
	 */
	public function __construct(array $strategies)
	{
		$this->strategies = $strategies;
	}

	public function resolve(array $context): string
	{
		foreach ($this->strategies as $strategy) {
			$image_url = $strategy->resolve($context);
			if (is_string($image_url) && trim($image_url) !== '') {
				return trim($image_url);
			}
		}

		return (string) ($context['thumbnail_url'] ?? '');
	}
}

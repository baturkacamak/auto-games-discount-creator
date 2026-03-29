<?php

namespace AutoGamesDiscountCreator\Core\Utility\Image\Strategy;

interface ImageSourceStrategyInterface
{
	public function resolve(array $context): ?string;
}

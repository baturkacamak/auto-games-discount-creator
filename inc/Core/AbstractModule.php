<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 19:51
 */

namespace AutoGamesDiscountCreator\Core;

use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctionsInterface;
use JetBrains\PhpStorm\Pure;

abstract class AbstractModule implements Module
{
	protected WordPressFunctions $wpFunctions;

	#[Pure] public function __construct(WordPressFunctionsInterface $wpFunctions)
	{
		$this->wpFunctions = $wpFunctions;
		$this->wpFunctions->setClass($this);
	}
}

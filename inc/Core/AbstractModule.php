<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 19:51
 */

namespace AutoGamesDiscountCreator\Core;

use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use JetBrains\PhpStorm\Pure;

abstract class AbstractModule implements Module
{
	protected WordPressFunctions $wpFunction;

	#[Pure] public function __construct()
	{
		$this->wpFunction = new WordPressFunctions($this);
	}
}

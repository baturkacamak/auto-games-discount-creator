<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 19:51
 */

namespace AutoGamesDiscountCreator\Core\Module;

use AutoGamesDiscountCreator\Core\Module;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctionsInterface;

abstract class AbstractModule implements Module
{
	protected WordPressFunctions $wpFunctions;

	public function __construct(?WordPressFunctionsInterface $wpFunctions = null)
	{
		$this->wpFunctions = $wpFunctions instanceof WordPressFunctionsInterface
			? $wpFunctions
			: new WordPressFunctions($this);
		$this->wpFunctions->setClass($this);
	}
}

<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 19:49
 */

namespace AutoGamesDiscountCreator\Core;

interface Module
{

	/**
	 * Add hooks from this method
	 *
	 * @return void
	 */
	public function setup();
}

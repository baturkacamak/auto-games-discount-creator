<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 19/2/23
 * Time: 16:26
 */

namespace AutoGamesDiscountCreator\Core\Module\Initializer;

use AutoGamesDiscountCreator\Core\Module;

class ModuleInitializer implements ModuleInitializerInterface
{
	/**
	 * Initializes all the specified module classes.
	 *
	 * @param array $modules An array of module class names.
	 */
	public function initModules(array $modules): void
	{
		/** @var Module $module */
		foreach ($modules as $module) {
			(new $module())->setup();
		}
	}
}

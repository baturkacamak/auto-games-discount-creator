<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 19/2/23
 * Time: 16:24
 */

namespace AutoGamesDiscountCreator\Core\Module\Registry;

/**
 * Interface for module registrar classes.
 */
interface ModuleRegistrarInterface
{
	/**
	 * Registers all the modules in the `Modules` directory and returns an array of module class names.
	 *
	 * @return array An array of module class names.
	 */
	public function registerModules(): array;
}

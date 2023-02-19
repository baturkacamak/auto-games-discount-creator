<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 13/2/23
 * Time: 21:22
 */

namespace AutoGamesDiscountCreator\Core;

use AutoGamesDiscountCreator\Core;
use AutoGamesDiscountCreator\Core\Module\Initializer\ModuleInitializerInterface;
use AutoGamesDiscountCreator\Core\Module\Registry\ModuleRegistrarInterface;

/**
 * Modules Manager Class
 *
 * This class is responsible for registering and initializing all the modules in the `Modules` directory.
 *
 * @package AutoGamesDiscountCreator\Core
 */
class ModulesManager
{
	/**
	 * An array to store the registered module class names.
	 *
	 * @var array
	 */
	private $modules = [];

	/**
	 * ModulesManager constructor.
	 *
	 * In the constructor, the `registerModules` and `initModules` methods are called.
	 *
	 * @param ModuleRegistrarInterface $module_registrar The module registrar dependency.
	 * @param ModuleInitializerInterface $module_initializer The module initializer dependency.
	 */
	public function __construct(ModuleRegistrarInterface $module_registrar, ModuleInitializerInterface $module_initializer)
	{
		$this->modules = $module_registrar->registerModules();
		$module_initializer->initModules($this->modules);
	}
}

<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 13/2/23
 * Time: 21:22
 */

namespace AutoGamesDiscountCreator\Core;

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
	 */
	public function __construct()
	{
		$this->registerModules();
		$this->initModules();
	}

	/**
	 * A method to register all the modules in the `Modules` directory.
	 *
	 * The method scans the directory and checks if the file is a valid module class that extends the `AbstractModule`
	 * class. If it is, the class name is added to the `$modules` array.
	 */
	private function registerModules()
	{
		$modules_path = __DIR__ . '/../Modules/';

		$modules = scandir($modules_path);
		foreach ($modules as $module) {
			if ($module === '.' || $module === '..') {
				continue;
			}

			$module_name = pathinfo($module, PATHINFO_FILENAME);
			$class_name  = "AutoGamesDiscountCreator\\Modules\\{$module_name}";
			if (class_exists($class_name) && is_subclass_of($class_name, AbstractModule::class)) {
				$this->modules[] = $class_name;
			}
		}
	}

	/**
	 * A method to initialize all the registered modules.
	 *
	 * The method creates a new instance of each module class stored in the `$modules` array.
	 */
	private function initModules()
	{
		/** @var Module $module */
		foreach ($this->modules as $module) {
			(new $module())->setup();
		}
	}
}

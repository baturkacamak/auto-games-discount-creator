<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 19/2/23
 * Time: 16:56
 */

use AutoGamesDiscountCreator\Core\Module\Registry\ModuleRegistrar;
use PHPUnit\Framework\TestCase;

class ModuleRegistrarTest extends TestCase
{
	public function testRegisterModulesReturnsArrayOfModuleClassNames()
	{
		// Create a mock directory for the modules
		$modules_directory = ModuleRegistrar::MODULES_PATH;
		file_put_contents(
			$modules_directory . '/TestModule.php',
			"<?php\n namespace AutoGamesDiscountCreator\Modules; \n class TestModule extends \AutoGamesDiscountCreator\Core\Module\AbstractModule { public function setup() {} }"
		);

		// Call the registerModules method
		$module_registrar = new ModuleRegistrar();
		$modules          = $module_registrar->registerModules();

		// Filter the array to only include the TestModule class
		$modules =
			array_values(
				array_filter($modules, function ($module) {
					return $module === 'AutoGamesDiscountCreator\Modules\TestModule';
				})
			);

		// Assert that the method returns the expected array of module class names
		$this->assertEquals(['AutoGamesDiscountCreator\Modules\TestModule'], $modules);

		// Clean up the test directory
		unlink($modules_directory . '/TestModule.php');
	}
}

<?php

/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 19/2/23
 * Time: 15:41
 */

use AutoGamesDiscountCreator\Core\Module\Initializer\ModuleInitializerInterface;
use AutoGamesDiscountCreator\Core\Module\Registry\ModuleRegistrarInterface;
use AutoGamesDiscountCreator\Core\ModulesManager;
use PHPUnit\Framework\TestCase;

class ModulesManagerTest extends TestCase
{
	public function testConstructorRegistersAndInitializesModules()
	{
		// Create mock objects for the dependencies
		$module_registrar_mock = $this->createMock(ModuleRegistrarInterface::class);
		$module_initializer_mock = $this->createMock(ModuleInitializerInterface::class);

		// Define the expected module class names
		$expected_modules = [
			'AutoGamesDiscountCreator\Modules\Module1',
			'AutoGamesDiscountCreator\Modules\Module2',
		];

		// Set up the mock object behaviors
		$module_registrar_mock->expects($this->once())
		                      ->method('registerModules')
		                      ->willReturn($expected_modules);

		$module_initializer_mock->expects($this->once())
		                        ->method('initModules')
		                        ->with($expected_modules);

		// Create a new ModulesManager object with the mock dependencies
		$manager = new ModulesManager($module_registrar_mock, $module_initializer_mock);
	}
}

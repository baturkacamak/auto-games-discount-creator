<?php

namespace AutoGamesDiscountCreator\Core\Module\Initializer;

/**
 * Interface for module initializer classes.
 */
interface ModuleInitializerInterface
{
	/**
	 * Initializes all the specified module classes.
	 *
	 * @param array $modules An array of module class names.
	 */
	public function initModules(array $modules): void;
}

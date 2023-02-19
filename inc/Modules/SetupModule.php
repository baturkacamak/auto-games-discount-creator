<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 15:23
 */

namespace AutoGamesDiscountCreator\Modules;

use AutoGamesDiscountCreator\Core\Module\AbstractModule;
use AutoGamesDiscountCreator\Core\Utility\Database;

class SetupModule extends AbstractModule
{
	/**
	 * Class SetupModule
	 *
	 * @throws \Exception
	 * @package AutoGamesDiscountCreator
	 */
	public function init()
	{
		$default_columns = [
			'ID'         => ['int', 'NOT null', 'AUTO_INCREMENT', 'PRIMARY KEY'],
			'status'     => ['char(1)', 'default 1'],
			'created_at' => ['timestamp', 'DEFAULT now()', 'not null'],
		];

		Database::getInstance()->create(
			'games',
			array_merge(
				[
					'name' => ['varchar(150)'],
					'url'  => ['varchar(150)'],
				],
				$default_columns
			)
		);

		Database::getInstance()->create(
			'prices',
			array_merge(
				[
					'game_id' => ['int'],
					'price'   => ['double'],
					'region'  => ['varchar(5)', 'default \'TR\''],
					'cut'     => ['int(3)'],
				],
				$default_columns
			)
		);
		Database::getInstance()->create(
			'rambouillet_posts',
			array_merge(
				[
					'price_id'         => ['int'],
					'status_wordpress' => ['char(1)', 'DEFAULT 0'],
				],
				$default_columns
			)
		);
	}

	public function setup()
	{
		$this->init();
	}
}

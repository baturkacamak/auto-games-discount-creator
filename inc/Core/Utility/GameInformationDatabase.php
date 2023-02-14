<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 13/2/23
 * Time: 22:44
 */

namespace AutoGamesDiscountCreator\Core\Utility;

use Exception;
use stdClass;

class GameInformationDatabase
{
	/**
	 * Inserts game information into the database.
	 *
	 * @param stdClass $gameInfo An instance of the stdClass class.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function insertGameInformation(stdClass $gameInfo): array
	{
		if ($this->isValidGameInformation($gameInfo)) {
			$game_medoo = $this->insertOrGetGamesTable($gameInfo);
			$price_medoo = $this->insertOrGetPricesTable($game_medoo, $gameInfo);
			$this->insertOrGetRambouilletPostsTable($price_medoo);

			return $price_medoo;
		}
	}

	/**
	 * Check if the game information is valid.
	 *
	 * @param stdClass $gameInfo An instance of the stdClass class.
	 *
	 * @return bool
	 */
	private function isValidGameInformation($gameInfo): bool
	{
		return isset($gameInfo->name) && !empty($gameInfo->name)
		       && !empty($gameInfo->cut) && !empty($gameInfo->url);
	}

	/**
	 * Inserts the game information into the 'games' table or retrieves the ID of the existing record.
	 *
	 * @param stdClass $gameInfo An instance of the stdClass class.
	 *
	 * @return array
	 * @throws Exception
	 */
	private function insertOrGetGamesTable($gameInfo): array
	{
		return Database::getInstance()->insertOrGet(
			'games',
			[
				'name' => $gameInfo->name,
				'url'  => $gameInfo->url,
			]
		);
	}

	/**
	 * Inserts the price information into the 'prices' table or retrieves the ID of the existing record.
	 *
	 * @param array $gameMedoo The information of the game in the 'games' table.
	 * @param stdClass $gameInfo An instance of the stdClass class.
	 *
	 * @return array
	 * @throws Exception
	 */
	private function insertOrGetPricesTable($gameMedoo, $gameInfo): array
	{
		return Database::getInstance()->insertOrGet(
			'prices',
			[
				'game_id' => (int)$gameMedoo['ID'],
				'price'   => (double)$gameInfo->price,
				'cut'     => (int)$gameInfo->cut,
			]
		);
	}

	/**
	 * Inserts the price information into the 'rambouillet_posts' table or retrieves the ID of the existing record.
	 *
	 * @param array $priceMedoo The information of the price in the 'prices' table.
	 *
	 * @return void
	 * @throws Exception
	 */
	private function insertOrGetRambouilletPostsTable($priceMedoo): void
	{
		if (is_array($priceMedoo)) {
			Database::getInstance()->insertOrGet(
				'rambouillet_posts',
				[
					'price_id' => (int)$priceMedoo['ID'],
				]
			);
		}
	}
}



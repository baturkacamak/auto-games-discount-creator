<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use Exception;
use Medoo\Medoo;

/**
 * Class Database
 *
 * This class provides a convenient interface for the Medoo database library.
 *
 * @package AutoGamesDiscountCreator\Core\Utility
 */
class Database extends Medoo
{
	/**
	 * @var Database A static instance of the Database class.
	 */
	private static Database $instance;

	/**
	 * Database constructor.
	 *
	 * Creates a new instance of the Medoo library using the WordPress database configuration.
	 *
	 * @throws Exception If an error occurs while connecting to the database.
	 */
	private function __construct()
	{
		global $wpdb;

		$data = [
			'database_type' => 'mysql',
			'database_name' => DB_NAME,
			'server'        => DB_HOST,
			'username'      => DB_USER,
			'password'      => DB_PASSWORD,
			'charset'       => 'utf8mb4',
			'collation'     => 'utf8mb4_unicode_ci',
			'prefix'        => $wpdb->prefix . 'game_scraper_',
			'command'       => [
				'SET SQL_MODE=ANSI_QUOTES',
			],
		];

		parent::__construct($data);
	}

	/**
	 * Returns the single instance of the Database class.
	 *
	 * @return Database
	 * @throws Exception
	 */
	public static function getInstance(): self
	{
		static $database = null; // cache

		if (null === $database) {
			$database = new self();
		}

		return $database;
	}

	/**
	 * Inserts a new record if it does not exist, otherwise returns the existing record.
	 *
	 * @param string $tableName The name of the table to insert the data into.
	 * @param array $data The data to insert into the table.
	 *
	 * @return array The inserted or existing record.
	 * @throws Exception
	 */
	public function insertOrGet(string $tableName, array $data): array
	{
		$existing_record = self::getInstance()->get($tableName, '*', $data);
		if (!$existing_record) {
			self::getInstance()->insert($tableName, $data);
			$existing_record = array_merge(['ID' => self::getInstance()->id()], $data);
		}

		return $existing_record;
	}

	/**
	 * Inserts a new record or updates an existing one.
	 *
	 * @param string $tableName The name of the table to insert or update the data in.
	 * @param array $data The data to insert or update in the table.
	 * @param array $where The condition for updating an existing record.
	 *
	 * @return bool|int The ID of the inserted record, or false if an existing record was updated.
	 */
	public function insertOrUpdate(string $tableName, array $data, array $where)
	{
		$where['LIMIT']  = 1;
		$existing_record = self::getInstance()->get($tableName, '*', $where);
		if ($existing_record) {
			self::getInstance()->update($tableName, $data, ['ID' => $existing_record['ID']]);

			return false;
		}

		self::getInstance()->insert($tableName, $data);

		return self::getInstance()->id();
	}
}

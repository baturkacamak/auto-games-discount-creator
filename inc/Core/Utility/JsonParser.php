<?php

/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 13/2/23
 * Time: 22:06
 */

/**
 * Class for parsing JSON data
 */

namespace AutoGamesDiscountCreator\Core\Utility;

class JsonParser
{
	private $file;

	public function __construct($file)
	{
		$this->file = $file;
	}


	/**
	 * Parse a JSON file and return the data as an associative array.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function parse()
	{
		if (!file_exists($this->file)) {
			throw new \Exception("File not found: {$this->file}");
		}

		$json_data = file_get_contents($this->file);
		$data      = json_decode($json_data, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception("Error parsing JSON data: " . json_last_error_msg());
		}

		return $data;
	}
}

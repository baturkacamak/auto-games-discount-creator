<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 18/2/23
 * Time: 23:29
 */

if (!function_exists('agdc_get')) {
	/**
	 * Retrieve a value from an array using a path string.
	 *
	 * @param string $path The path string, e.g. "keyA/keyB/keyC".
	 * @param array $array The array to extract the value from.
	 * @param mixed $default The default value to return if the path is not found.
	 *
	 * @return mixed
	 */
	function agdc_get(string $path, array $array, $default = null)
	{
		$path = explode('/', $path);

		foreach ($path as $key) {
			if (!is_array($array) || !array_key_exists($key, $array)) {
				return $default;
			}
			$array = $array[$key];
		}

		return $array;
	}
}

<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 18/2/23
 * Time: 23:33
 */

use PHPUnit\Framework\TestCase;

class AgdcGetTest extends TestCase
{
	public function testAgdcGetReturnsExpectedValueForExistingKey()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => [
					'c' => 'test_value',
				],
			],
		];

		// Call agdc_get with an existing key
		$output = agdc_get('a/b/c', $input_array);

		// Assert that the function returns the expected output
		$this->assertEquals('test_value', $output);
	}

	public function testAgdcGetReturnsDefaultValueForNonexistentKey()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => [
					'c' => 'test_value',
				],
			],
		];

		// Call agdc_get with a nonexistent key
		$output = agdc_get('a/b/d', $input_array, 'default_value');

		// Assert that the function returns the default value
		$this->assertEquals('default_value', $output);
	}

	public function testAgdcGetReturnsDefaultValueForEmptyArray()
	{
		// Define an empty input array
		$input_array = [];

		// Call agdc_get with a key
		$output = agdc_get('a/b/c', $input_array, 'default_value');

		// Assert that the function returns the default value
		$this->assertEquals('default_value', $output);
	}

	public function testAgdcGetReturnsExpectedValueForNumericIndex()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => [
					'c',
					'd',
					'e',
				],
			],
		];

		// Call agdc_get with a numeric index
		$output = agdc_get('a/b/0', $input_array);

		// Assert that the function returns the expected output
		$this->assertEquals('c', $output);
	}

	public function testAgdcGetReturnsExpectedValueForEmptyPath()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => 'test_value',
			],
		];

		// Call agdc_get with an empty path
		$output = agdc_get('', $input_array, 'default_value');

		// Assert that the function returns the default value
		$this->assertEquals('default_value', $output);
	}

	public function testAgdcGetReturnsExpectedValueForNullDefault()
	{
		// Define the input array
		$input_array = [
			'a' => [
				'b' => 'test_value',
			],
		];

		// Call agdc_get with a nonexistent key and a null default value
		$output = agdc_get('a/b/c', $input_array, null);

		// Assert that the function returns null
		$this->assertNull($output);
	}

}

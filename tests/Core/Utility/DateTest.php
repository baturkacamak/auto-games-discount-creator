<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 17/2/23
 * Time: 17:55
 */

namespace AutoGamesDiscountCreator\Core\Utility;

use PHPUnit\Framework\TestCase;

class DateTest extends TestCase
{
	public function testGetTurkishName()
	{
		$date = new Date();

		// Test that the method returns the Turkish name of a given date
		$this->assertEquals('Pazartesi', $date->getTurkishName('2023-02-13', 'l'));
		$this->assertEquals('Şubat', $date->getTurkishName('2023-02-01', 'F'));
		$this->assertEquals('Sal', $date->getTurkishName('2023-02-14', 'D'));

		// Test that the method handles edge cases correctly
		$this->assertEquals('Temmuz', $date->getTurkishName('2023-07-01', 'F'));
		$this->assertEquals('Ocak', $date->getTurkishName('2023-01-01', 'F'));
		$this->assertEquals('Mayıs 1, 2023', $date->getTurkishName('2023-05-01', 'F j, Y'));
	}
}

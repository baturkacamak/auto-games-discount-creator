<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 17:09
 */

namespace AutoGamesDiscountCreator\Core\Utility;

/**
 * Class Date
 *
 * @package AutoGamesDiscountCreator\Utility
 */
class Date
{
	/**
	 * An array containing the Turkish names of the days of the week and months of the year.
	 */
	const TURKISH_DATE_NAMES = [
		'Monday'    => 'Pazartesi',
		'Tuesday'   => 'Salı',
		'Wednesday' => 'Çarşamba',
		'Thursday'  => 'Perşembe',
		'Friday'    => 'Cuma',
		'Saturday'  => 'Cumartesi',
		'Sunday'    => 'Pazar',
		'January'   => 'Ocak',
		'February'  => 'Şubat',
		'March'     => 'Mart',
		'April'     => 'Nisan',
		'May'       => 'Mayıs',
		'June'      => 'Haziran',
		'July'      => 'Temmuz',
		'August'    => 'Ağustos',
		'September' => 'Eylül',
		'October'   => 'Ekim',
		'November'  => 'Kasım',
		'December'  => 'Aralık',
		'Mon'       => 'Pts',
		'Tue'       => 'Sal',
		'Wed'       => 'Çar',
		'Thu'       => 'Per',
		'Fri'       => 'Cum',
		'Sat'       => 'Cts',
		'Sun'       => 'Paz',
		'Jan'       => 'Oca',
		'Feb'       => 'Şub',
		'Mar'       => 'Mar',
		'Apr'       => 'Nis',
		'Jun'       => 'Haz',
		'Jul'       => 'Tem',
		'Aug'       => 'Ağu',
		'Sep'       => 'Eyl',
		'Oct'       => 'Eki',
		'Nov'       => 'Kas',
		'Dec'       => 'Ara',
	];

	/**
	 * Returns the Turkish name of a given date.
	 *
	 * @param string $strTime The date and time string to convert to Turkish. Defaults to 'now'.
	 * @param string $dateFormat The format string to use for converting the date to a string. Defaults to 'F'.
	 *
	 * @return false|string|string[] The Turkish name of the date.
	 */
	public function getTurkishName($strTime = 'now', $dateFormat = 'F')
	{
		setlocale(LC_TIME, 'tr_TR.UTF-8');

		$int_date_format = $this->getIntDateFormat($dateFormat);

		$date_name = strftime($int_date_format, strtotime($strTime));

		if (array_search($date_name, self::TURKISH_DATE_NAMES)) {
			return $date_name;
		} else {
			$date_name = date($dateFormat, strtotime($strTime));

			$date_name = $this->replaceEnglishNamesWithTurkishNames($date_name);

			if ($this->containsMayisWithoutMonthFormat($date_name, $dateFormat)) {
				$date_name = str_replace('Mayıs', 'May', $date_name);
			}
		}

		return $date_name;
	}

	/**
	 * Returns the int date format equivalent of a given date format.
	 *
	 * @param string $dateFormat
	 *
	 * @return string
	 */
	private function getIntDateFormat(string $dateFormat): string
	{
		return match ($dateFormat) {
			'F' => '%B',
			'D' => '%a',
			'l' => '%A',
			'M' => '%b',
			default => $dateFormat,
		};
	}

	/**
	 * Replaces the English names in a date string with their Turkish equivalents.
	 *
	 * @param string $date_string
	 *
	 * @return string
	 */
	private function replaceEnglishNamesWithTurkishNames(string $date_string): string
	{
		foreach (self::TURKISH_DATE_NAMES as $english_day_name => $turkish_day_name) {
			$date_string = str_replace($english_day_name, $turkish_day_name, $date_string);
		}

		return $date_string;
	}

	/**
	 * Returns whether a date string contains the word "Mayıs" without the month format.
	 *
	 * @param string $dateString
	 * @param string $dateFormat
	 *
	 * @return bool
	 */
	private function containsMayisWithoutMonthFormat(string $dateString, string $dateFormat): bool
	{
		return str_contains($dateString, 'Mayıs') && !str_contains($dateFormat, 'F');
	}
}

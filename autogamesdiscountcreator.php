<?php

/**
 * Plugin Name:     Auto Games Discount Creator
 * Plugin URI:      https://github.com/baturkacamak/auto-games-discount-creator
 * Description:     A WordPress plugin that allows you to create game discount posts automatically by scraping data from isthereanydeal.com. The plugin fetches the latest game deals and creates posts on your WordPress site, making it easier to keep your site updated with the latest game discounts.
 * Author:          Batur Kacamak
 * Author URI:      https://batur.info
 * Text Domain:     auto-games-discount-creator
 * Domain Path:     /languages
 * Version:         1.1.0
 * License:         GPL-3.0+
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package         AutoGamesDiscountCreator
 */

use AutoGamesDiscountCreator\AutoGamesDiscountCreator;

define('AGDC_VERSION', '0.1.0');
define('AGDC_PLUGIN_FILE', __FILE__);
define('AGDC_PLUGIN_DIR', __DIR__);
define('AGDC_PLUGIN_URL', plugin_dir_url(__FILE__));
defined('AGDC_BASE_FILE') or define(
	'AGDC_BASE_FILE',
	str_replace(dirname(__FILE__, 2) . '/', '', __FILE__)
);

if (!defined('FW') && file_exists(__DIR__ . '/plugins/unyson/framework/bootstrap.php')) {
	require_once __DIR__ . '/plugins/unyson/framework/bootstrap.php';
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if (!function_exists('agdcPhpVersionNotice')) {
	/**
	 * Print admin notice regarding having an old version of PHP.
	 *
	 * @since 0.2
	 */
	function agdcPhpVersionNotice()
	{
		ob_start();
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
				/* translators: %s: required PHP version */
					esc_html__(
						'The Auto Games Discount Creator plugin requires PHP %s. Please contact your host to update your PHP version.',
						'pwa'
					),
					'5.6+'
				);
				?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	if (version_compare(phpversion(), '5.6', '<')) {
		add_action('admin_notices', 'agdcPhpVersionNotice');

		return;
	}
}

if (!function_exists('agdcIncorrectSlugNotice')) {
	/**
	 * Print admin notice if plugin installed with incorrect slug (which impacts WordPress's auto-update system).
	 *
	 * @since 0.2
	 */
	function agdcIncorrectSlugNotice()
	{
		$actual_slug = basename(AGDC_PLUGIN_DIR);
		?>
		<div class="notice notice-warning">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
					/* translators: %1$s is the current directory name, and %2$s is the required directory name */
						__(
							'You appear to have installed the Auto_Games_Discount_Creator plugin incorrectly.' .
							' It is currently installed in the <code>%1$s</code> directory,' .
							' but it needs to be placed in a directory named <code>%2$s</code>.' .
							' Please rename the directory.' .
							' This is important for WordPress plugin auto-updates.',
							'pwa'
						),
						$actual_slug,
						'rambouillet'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	if ('wp-game-discount-poster' !== basename(AGDC_PLUGIN_DIR)) {
		add_action('admin_notices', 'agdcIncorrectSlug');
	}
}

if (!function_exists('agdc_get')) {
	/**
	 * Retrieve a value from an array using a path string.
	 *
	 * @param string $path    The path string, e.g. "keyA/keyB/keyC".
	 * @param array  $array   The array to extract the value from.
	 * @param mixed  $default The default value to return if the path is not found.
	 *
	 * @return mixed
	 */
	function agdc_get(string $path, array $array, $default = null)
	{
		$path = explode('/', $path);

		foreach ($path as $key) {
			if (!array_key_exists($key, $array)) {
				return $default;
			}
			$array = $array[$key];
		}

		return $array;
	}
}


AutoGamesDiscountCreator::getInstance()->init();

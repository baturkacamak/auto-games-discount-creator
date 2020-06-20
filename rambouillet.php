<?php

/**
 * Plugin Name:     Rambouillet
 * Plugin URI:      `https://github.com/baturkacamak/rambouillet`
 * Description:     Auto games discount creator
 * Author:          Batur Kacamak
 * Author URI:      https://batur.info
 * Text Domain:     rambouillet
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Rambouillet
 */

use Rambouillet\Rambouillet;

define('RAMT_VERSION', '0.1.0');
define('RAMT_PLUGIN_FILE', __FILE__);
define('RAMT_PLUGIN_DIR', __DIR__);
define('RAMT_PLUGIN_URL', plugin_dir_url(__FILE__));
defined('RAMT_BASE_FILE') or define(
    'RAMT_BASE_FILE',
    str_replace(dirname(__FILE__, 2) . '/', '', __FILE__)
);

if (!defined('FW') && file_exists(__DIR__ . '/plugins/unyson/framework/bootstrap.php')) {
    require_once __DIR__ . '/plugins/unyson/framework/bootstrap.php';
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (!function_exists('ramtPhpVersionNotice')) {
    /**
     * Print admin notice regarding having an old version of PHP.
     *
     * @since 0.2
     */
    function ramtPhpVersionNotice()
    {
        ob_start();
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                /* translators: %s: required PHP version */
                    esc_html__(
                        'The rambouillet plugin requires PHP %s. Please contact your host to update your PHP version.',
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
        add_action('admin_notices', 'ramtPhpVersionNotice');
        return;
    }
}

if (!function_exists('ramtIncorrectSlug')) {
    /**
     * Print admin notice if plugin installed with incorrect slug (which impacts WordPress's auto-update system).
     *
     * @since 0.2
     */
    function ramtIncorrectSlug()
    {
        $actual_slug = basename(RAMT_PLUGIN_DIR);
        ?>
        <div class="notice notice-warning">
            <p>
                <?php
                echo wp_kses_post(
                    sprintf(
                    /* translators: %1$s is the current directory name, and %2$s is the required directory name */
                        __(
                            'You appear to have installed the Rambouillet plugin incorrectly.' .
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

    if ('rambouillet' !== basename(RAMT_PLUGIN_DIR)) {
        add_action('admin_notices', 'ramtIncorrectSlug');
    }
}



Rambouillet::getInstance()->init();
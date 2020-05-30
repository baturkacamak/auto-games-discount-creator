<?php

namespace Rambouillet;

if (!class_exists('Rambouillet\Requirement')) {
    /**
     * Class Requirement
     *
     * @package Rambouillet
     */
    class Requirement
    {

        /**
         * Requirement constructor.
         */
        public function __construct()
        {
            add_filter('fw_extensions_locations', [$this, 'filterPluginAwesomeExtensions']);

            if (!function_exists('is_plugin_active')) {
                include_once ABSPATH . '/wp-admin/includes/plugin.php';
            }
            add_action('admin_notices', [$this, 'adminInit']);
            if (is_plugin_inactive('unyson/unyson.php')) {
                add_action('admin_notices', [$this, 'rambouilletRequirementAdminNotice']);
            }
        }

        /**
         *
         */
        public function adminInit()
        {
            if (
                basename($_SERVER['PHP_SELF']) !== 'plugins.php' && is_plugin_inactive(
                    'unyson/unyson.php'
                )
            ) {
                ?>
                <p>
                    <?php _e(
                        sprintf(
                            'After installation, please proceed to <a href="%s/plugins.php">Plugins</a> 
                                    to activate them.',
                            get_admin_url()
                        ),
                        'rambouillet'
                    ); ?></p>
                <?php
            }
        }

        // wp error messages system function

        /**
         *
         */
        public function rambouilletRequirementAdminNotice()
        {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php _e(
                        'Rambouillet requires <a href="' . get_admin_url() .
                        '/plugin-install.php?tab=plugin-information&amp;plugin=unyson&' .
                        'amp;TB_iframe=true" class="thickbox" aria-label="More information about PLUGIN NAME"' .
                        ' data-title="Unyson">Unyson Framework</a>',
                        'rambouillet'
                    ); ?>
                </p>
                <button type="button"
                        class="notice-dismiss"
                >
                    <span class="screen-reader-text">
                        <?php _e(
                            'Dismiss this notice.',
                            'rambouillet'
                        ); ?></span>
                </button>
            </div>
            <?php
        }

        /**
         * @param $locations
         * @return mixed
         * @internal
         */
        public function filterPluginAwesomeExtensions($locations)
        {
            $locations[dirname(__FILE__) . '/extensions'] = plugin_dir_url(__FILE__) . 'extensions';
            return $locations;
        }
    }
}

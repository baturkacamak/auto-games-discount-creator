<?php

namespace Rambouillet;

use Curl\Curl;
use Rambouillet\Unyson\PluginSettings;
use Rambouillet\Util\Medoo;

/**
 * Class Rambouillet
 *
 * @package Rambouillet
 */
class Rambouillet
{

    /**
     * The one true instance.
     */
    private static $instance;
    /**
     * @var Curl
     */
    private $curl;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        return self::$instance = $this;
    }

    /**
     * Get singleton instance.
     *
     * @since 1.5
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     *
     */
    public function init()
    {
        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->setHeader(
            'user-agent',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36
             (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36'
        );
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . '/wp-admin/includes/plugin.php';
        }


        $this->actions();
    }

    /**
     *
     */
    private function actions()
    {
        register_activation_hook(RAMBUILLET_FILE, [$this, 'actionActivateRambouillet']);
        if (is_plugin_active(RAMBUILLET_FILE)) {
            add_action('init', [$this, 'actionInit']);
        }
    }

    /**
     *
     */
    public function actionInit()
    {
        new Requirement();

        if (defined('FW')) {
            $pluginSettings = new PluginSettings('rambouillet');

            new Schedule($this->curl, $pluginSettings);
        }
    }

    /**
     *
     */
    public function actionActivateRambouillet()
    {
        new Setup();
    }
}

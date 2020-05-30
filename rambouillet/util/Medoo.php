<?php

namespace Rambouillet\Util;

/**
 * Class Medoo
 *
 * @package rambouillet\util
 */
class Medoo
{
    /**
     * @var bool
     */
    public static $instance = false;

    /**
     * HT_Medoo constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param $table_name
     * @param $data
     *
     * @return array
     */
    public static function insertNotExists($table_name, $data)
    {
        if (
            ! ($table_data = self::getInstance()->get(
                $table_name,
                '*',
                $data
            ))
        ) {
            self::getInstance()->insert(
                $table_name,
                $data
            );
            $table_data = array_merge(
                ['ID' => self::getInstance()->id()],
                $data
            );
        }


        return $table_data;
    }

    /**
     * @return bool|\Medoo\Medoo
     */
    public static function getInstance()
    {
        if (! self::$instance) {
            global $wpdb;

            $data           = [
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
            self::$instance = new \Medoo\Medoo($data);
        }

        return self::$instance;
    }

    /**
     * @param $table_name
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public static function insertOrUpdateExist($table_name, $data, $where)
    {
        if (
            ! self::getInstance()->updateIfExists(
                $table_name,
                $data,
                $where
            )
        ) {
            self::getInstance()->insert(
                $table_name,
                $data
            );

            return self::getInstance()->id();
        }

        return false;
    }

    /**
     * @param $table_name
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public static function updateIfExists($table_name, $data, $where)
    {
        $where['LIMIT'] = 1;
        if (
            $table_data = self::getInstance()->get(
                $table_name,
                '*',
                $where
            )
        ) {
            return self::getInstance()->update(
                $table_name,
                $data,
                ['ID' => $table_data['ID']]
            );
        }

        return false;
    }
}

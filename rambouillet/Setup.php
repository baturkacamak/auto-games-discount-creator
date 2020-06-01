<?php

namespace Rambouillet;

use Rambouillet\Util\Medoo;

if (!class_exists('Rambouillet\Setup')) {
    /**
     * Class Setup
     *
     * @package Rambouillet
     */
    class Setup
    {
        /**
         * Setup constructor.
         */
        public function __construct()
        {
            $this->createTables();
        }


        /**
         *
         */
        private function createTables()
        {
            global $wpdb;

            $default_columns = [
                'ID' => ['int', 'NOT null', 'AUTO_INCREMENT', 'PRIMARY KEY'],
                'status' => ['char(1)', 'default 1'],
                'created_at' => ['timestamp', 'DEFAULT now()', 'not null'],
            ];

            Medoo::getInstance()->create(
                'games',
                array_merge(
                    [
                        'name' => ['varchar(150)'],
                        'url' => ['varchar(150)'],
                    ],
                    $default_columns
                )
            );

            Medoo::getInstance()->create(
                'prices',
                array_merge(
                    [
                        'game_id' => ['int'],
                        'price' => ['double'],
                        'region' => ['varchar(5)', 'default \'TR\''],
                        'cut' => ['int(3)'],
                    ],
                    $default_columns
                )
            );
            Medoo::getInstance()->create(
                'rambouillet_posts',
                array_merge(
                    [
                        'price_id' => ['int'],
                        'status_wordpress' => ['char(1)', 'DEFAULT 0'],
                    ],
                    $default_columns
                )
            );
        }
    }
}

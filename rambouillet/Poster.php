<?php

namespace Rambouillet;

use Curl\Curl;
use Rambouillet\Unyson\PluginSettings;
use Rambouillet\Util\Helper;
use Rambouillet\Util\Medoo;

if (!class_exists('Rambouillet\Poster')) {
    /**
     * Class Poster
     *
     * @package Rambouillet
     */
    class Poster
    {
        /**
         * @var Curl
         */
        private $curl;
        /**
         * @var array
         */
        private $game_data = [];
        private $tags;


        /**
         * Poster constructor.
         *
         * @param Curl $curl
         * @param PluginSettings $plugin_settings
         */
        public function __construct(Curl $curl, PluginSettings $plugin_settings)
        {
            $this->curl = $curl;

            $this->tags = $plugin_settings->get_values('tags');
            $this->init();
        }

        /**
         *
         */
        public function init()
        {
            $this->postWordpress();
        }

        public function getGameData($where = null)
        {
            $join = [
                "[>]{$this->table_prices}" => ['ID' => 'game_id'],
                "[>]{$this->table_price_meta}" => [
                    'ID' => 'game_id',
                    "{$this->table_prices}.ID" => 'price_id',
                ],
            ];

            //        $where_default = [
            //            "{$this->table_prices}.created_at[>=]" => $this->medoo::raw( 'CURDATE()' ),
            //            "{$this->table_prices}.created_at[<]"  => $this->medoo::raw( 'CURDATE() + INTERVAL 1 DAY' ),
            //        ];

            $select = [
                "{$this->table_prices}.ID(price_id)",
                "{$this->table_games}.ID(game_id)",
                "{$this->table_games}.name name",
                "{$this->table_prices}.price price",
                "{$this->table_prices}.cut cut",
                "{$this->table_games}.thumbnail_url thumbnail_url",
                "{$this->table_prices}.url url",
            ];

            $data = $this->medoo->select($this->table_games, $join, $select, $where);

            return $data;
        }

        public function postWordpress()
        {
            $game_data = Medoo::getInstance()->select(
                'games',
                [
                    '[>]prices' => ['ID' => 'game_id'],
                    '[>]rambouillet_posts' => ['ID' => 'price_id'],
                ],
                [
                    'games.name name',
                    'games.url url',
                    'prices.ID(price_id)',
                    'prices.price price',
                    'prices.cut cut',
                ],
                [
                    'status_wordpress' => 0,
                    'ORDER' => ['price' => 'DESC'],
                ]
            );
            $post_title = sprintf(
                "%d %s %d Steam İndirimleri",
                date('d'),
                Helper::getTurkishDate('F'),
                date('Y')
            );

            if (!function_exists('post_exists')) {
                include_once ABSPATH . 'wp-admin/includes/post.php';
            }

            if (
                0 === ($post_id = post_exists($post_title)) && $game_data && count(
                    $game_data
                ) > 0
            ) {
                $game_data = array_map(
                    function ($item) {
                        $exploded = explode('/', $item['url']);
                        $steam_id = $exploded[count($exploded) - 1];
                        //type = app || sub
                        $type = $exploded[3];
                        $item['thumbnail_url'] = "https://steamcdn-a.akamaihd.net/steam/{$type}s" .
                            "/{$steam_id}/header.jpg";
                        return $item;
                    },
                    $game_data
                );
                ob_start();
                include_once "content/content-tr.php";
                $content = ob_get_clean();

                $post = [
                    'post_content' => $content,
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'post_excerpt' => $post_title . ' ' . $this->tags,
                    'post_title' => $post_title,
                    'tags_input' => $this->tags,
                ];

                $post_id = wp_insert_post($post);

                if ($post_id) {
                    $message = $post_title . ' ' . $this->tags;
                    update_post_meta($post_id, '_wpas_mess', $message);

                    foreach ($game_data as $index => $game_datum) {
                        Medoo::getInstance()->update(
                            'rambouillet_posts',
                            ['status_wordpress' => 1],
                            ['price_id' => $game_datum['price_id']]
                        );
                    }
                }
            }

            return $post_id;
        }
    }
}

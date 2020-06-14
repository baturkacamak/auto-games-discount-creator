<?php

namespace Rambouillet;

use Curl\Curl;
use Exception;
use Rambouillet\Unyson\PluginSettings;
use Rambouillet\Utility\Helper;
use Rambouillet\Utility\Medoo;

if (!class_exists('Rambouillet\Poster')) {
    /**
     * Class Poster
     *
     * @package Rambouillet
     */
    class Poster
    {
        /**
         * @var array|null
         */
        private $tags;
        /**
         * @var array
         */
        private $medoo;


        /**
         * Poster constructor.
         * @throws Exception
         */
        public function __construct($type = 'daily')
        {
            if (!function_exists('post_exists')) {
                include_once ABSPATH . 'wp-admin/includes/post.php';
            }

            $this->tags = Rambouillet::getInstance()->pluginSettings->get_values('tags');

            $this->medoo = [
                'join' => [
                    '[>]prices' => ['ID' => 'game_id'],
                    '[>]rambouillet_posts' => ['prices.ID' => 'price_id'],
                ],
                'select' => [
                    'games.name name',
                    'games.url url',
                    'prices.ID(price_id)',
                    'prices.price price',
                    'prices.cut cut',
                ],
                'where' => [
                    'status_wordpress' => 0,
                    'ORDER' => ['price' => 'DESC'],
                ]
            ];

            if ($type === 'daily') {
                $this->postDaily();
            } else {
                $this->postFreeGames();
            }
        }

        public function postFreeGames()
        {
            $where = array_merge(['price' => 0], $this->medoo['where']);
            try {
                $free_game_data = Medoo::getInstance()->get(
                    'games',
                    $this->medoo['join'],
                    $this->medoo['select'],
                    $where
                );
            } catch (Exception $e) {
                throw $e;
            }

            if ($free_game_data) {
                $post_title = sprintf(
                    'Ucretsiz Oyun // %s // %d %s %d',
                    $free_game_data['name'],
                    date('d'),
                    Helper::getTurkishDate('F'),
                    date('Y')
                );
                if (0 === ($post_id = post_exists($post_title))) {
                    ob_start();
                    include 'content/content-free-tr.php';
                    $content = ob_get_clean();
                    $post = [
                        'post_content' => $content,
                        'post_status' => 'publish',
                        'post_author' => 1,
                        'post_excerpt' => $post_title . ' ' . $this->tags,
                        'post_title' => $post_title,
                        'tags_input' => $this->tags,
                        'post_category' => [14]
                    ];

                    $post_id = wp_insert_post($post);

                    if ($post_id) {
                        $message = $post_title . ' ' . $this->tags;
                        update_post_meta($post_id, '_wpas_mess', $message);

                        try {
                            Medoo::getInstance()->update(
                                'rambouillet_posts',
                                ['status_wordpress' => 1],
                                ['price_id' => $free_game_data['price_id']]
                            );
                        } catch (Exception $e) {
                            echo $e;
                        }
                    }
                }
            }
        }

        public function postDaily()
        {
            try {
                $where = array_merge(['price[>]' => 0], $this->medoo['where']);
                $game_data = Medoo::getInstance()->select(
                    'games',
                    $this->medoo['join'],
                    $this->medoo['select'],
                    $where
                );
            } catch (Exception $e) {
            }
            $post_title = sprintf(
                "%d %s %d Steam İndirimleri",
                date('d'),
                Helper::getTurkishDate('F'),
                date('Y')
            );

            if (
                0 === ($post_id = post_exists($post_title)) &&
                $game_data &&
                count($game_data) > 0
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
                    'post_category' => [1]
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

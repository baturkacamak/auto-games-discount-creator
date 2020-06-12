<?php

namespace Rambouillet;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Medoo\Medoo;
use Rambouillet\Unyson\PluginSettings;
use Rambouillet\Util\Helper;

if (!class_exists('Rambouillet\Scrape')) {
    /**
     * Class Scrape
     *
     * @package Rambouillet
     */
    class Scrape
    {
        /**
         * @var array
         */
        private static $statics = [
            'url' => [
                'base' => 'https://isthereanydeal.com',
                'lazy-deals' => 'ajax/data/lazy.deals.php',
            ],
        ];

        /**
         * @var array
         */
        private $data = [];
        /**
         * @var
         */
        private $home_html;
        /**
         * @var array
         */
        private $cached = [];
        /**
         * @var array
         */
        private $game_data = [];
        /**
         * @var Medoo
         */
        private $medoo;
        /**
         * @var PluginSettings
         */
        private $plugin_settings;
        /**
         * @var array|null
         */
        private $cookie;
        /**
         * @var Client
         */
        private $guzzle;
        /**
         * @var string
         */
        private $type;

        /**
         * Scrape constructor.
         * @param string $type
         * @throws GuzzleException
         */
        public function __construct($type = 'daily')
        {
            $this->type = $type;

            $jar = CookieJar::fromArray(['region' => 'tr', 'country' => 'TR%3ASpain'], 'isthereanydeal.com');

            $this->guzzle = new Client(
                [
                    'base_uri' => self::$statics['url']['base'],
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36' .
                            ' (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36',
                    ],
                    'cookies' => $jar
                ]
            );

            $this->init();
        }

        /**
         *
         * @throws Exception
         * @throws GuzzleException
         */
        public function init()
        {
            $this->saveRemoteHtml();
            $this->parseCacheHtml();
        }

        /**
         * @return void
         * @throws GuzzleException
         */
        private function saveRemoteHtml()
        {
            $queries = Rambouillet::getInstance()->pluginSettings->get_values('queries');
            $this->home_html = $this->getRemoteHomeHtml();
            foreach ($queries as $index => $query) {
                if ($query['query_type'] === $this->type) {
                    $cached_data = get_transient($query['query_key']);
                    if (!$cached_data) {
                        $html_game_list = $this->getRemoteGameList(
                            fw_akg('query_value', $query, false)
                        );
                        if ($html_game_list) {
                            $time = DAY_IN_SECONDS / 4;
                            if ($query['query_type'] === 'hourly') {
                                $time = HOUR_IN_SECONDS;
                            }
                            set_transient($query['query_key'], $html_game_list, $time);
                            $this->cached[] = [
                                'cache_type' => $query['query_key'],
                                'cache' => $html_game_list,
                            ];
                        }
                    } else {
                        $this->cached[] = [
                            'cache_type' => $query['query_key'],
                            'cache' => $cached_data,
                        ];
                    }
                }
            }
        }

        /**
         * @return string
         */
        private function getRemoteHomeHtml()
        {
            return $this->guzzle->request('GET')->getBody()->getContents();
        }

        /**
         * @param $query
         *
         * @return bool
         * @throws \GuzzleHttp\Exception\GuzzleException
         */
        private function getRemoteGameList($query)
        {
            $form_params = [
                'offset' => 0,
                'limit' => 100,
                'seen' => time() - 3000,
                'id' => (int)$this->getLazyId(),
                'timestamp' => time(),
                'options' => 'strict',
                'by' => 'price:asc',
                'filter' => $query,
            ];

            try {
                $response = $this->guzzle->request(
                    'POST',
                    self::$statics['url']['lazy-deals'],
                    ['form_params' => $form_params,]
                );

                if ($response->getStatusCode() === 200) {
                    $json = json_decode($response->getBody()->getContents());
                    if ($json && $json->status === 'success') {
                        return $json->data->html;
                    }
                }
                return false;
            } catch (GuzzleException $e) {
                throw $e;
            }
        }

        /**
         * @return string
         */
        private function getLazyId()
        {
            if (!$this->home_html) {
                $this->getRemoteHomeHtml();
            }

            $xpath = Helper::getXpath($this->home_html);
            /* Query all <td> nodes containing specified class name */
            $nodes = $xpath->query("//script");

            $id = '';
            foreach ($nodes as $index => $node) {
                preg_match_all('/id:\s*"([a-zA-Z0-9]+)"/', $node->nodeValue, $matches);
                if (isset($matches[1][0]) && !empty($matches[1][0])) {
                    $id = $matches[1][0];
                    break;
                }
            }

            //        LazyLoad ID
            return $id;
        }


        /**
         * @param $cache
         *
         * @return array|bool
         * @throws Exception
         */
        private function parseCacheHtml()
        {
            $data = [];


            if ($this->cached && is_array($this->cached)) {
                foreach ($this->cached as $index => $item) {
                    $xpath = Helper::getXpath($item['cache']);

                    $nodes = $xpath->query(
                        '//*[contains(@class, "game") and contains(@class, "seen")]'
                    );

                    if (isset($nodes) && $nodes->length > 0) {
                        foreach ($nodes as $index_nodes => $node) {
                            $game = new \stdClass();

                            $nameXpath = $xpath->query('//*[@class="noticeable"]', $node);

                            if ($nameXpath->item($index_nodes)) {
                                $game->name = $nameXpath->item($index_nodes)->nodeValue;

                                $priceXpath = $xpath->query(
                                    'descendant::*[contains(@class, "shopMark") and contains(@class, "g-low")]',
                                    $node
                                );

                                if ($priceXpath->item(0) === null) {
                                    return false;
                                }

                                $game->price = floatval(
                                    preg_replace(
                                        '/[^-0-9\.]/',
                                        '.',
                                        $this->getNodeValue($priceXpath)
                                    )
                                );

                                $game->url = untrailingslashit(
                                    $priceXpath->item(0)->getAttribute('href')
                                );

                                $cutXpath = $xpath->query(
                                    'descendant::*[contains(@class, "details")]/a//*[contains(@class, "cut")]',
                                    $node
                                );

                                $game->cut = $this->getNodeValue($cutXpath);

                                if (
                                    isset($game->name) && !empty($game->name) && !empty($game->cut) && !empty($game->url)
                                ) {
                                    $gameMedoo = \Rambouillet\Util\Medoo::insertNotExists(
                                        'games',
                                        [
                                            'name' => $game->name,
                                            'url' => $game->url,
                                        ]
                                    );

                                    $priceMedoo = \Rambouillet\Util\Medoo::insertNotExists(
                                        'prices',
                                        [
                                            'game_id' => (int)$gameMedoo['ID'],
                                            'price' => (double)$game->price,
                                            'cut' => (int)$game->cut,
                                        ]
                                    );

                                    if (is_array($priceMedoo)) {
                                        \Rambouillet\Util\Medoo::insertNotExists(
                                            'rambouillet_posts',
                                            [
                                                'price_id' => (int)$priceMedoo['ID'],
                                            ]
                                        );
                                    }
                                }
                                unset($game);
                            }
                        }
                    }
                }
            }
        }

        /**
         * @param null $xpath
         *
         * @return mixed
         */
        private function getNodeValue($xpath = null)
        {
            if (isset($xpath) && $xpath->length > 0) {
                foreach ($xpath as $index => $item) {
                    return $item->nodeValue;
                }
            }
        }
    }
}

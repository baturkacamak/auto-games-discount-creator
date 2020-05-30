<?php

namespace Rambouillet;

use Medoo\Medoo;
use Rambouillet\Unyson\PluginSettings;

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
                'home' => 'http://isthereanydeal.com',
                'lazy-deals' => 'https://isthereanydeal.com/ajax/data/lazy.deals.php',
            ],
        ];

        /**
         * @var \Curl\Curl
         */
        private $curl;
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
         * Scrape constructor.
         *
         * @param \Curl\Curl $curl
         * @param PluginSettings $plugin_settings
         */
        public function __construct(\Curl\Curl $curl, PluginSettings $plugin_settings)
        {
            $this->curl = $curl;
            $this->plugin_settings = $plugin_settings;
            $this->cookie = $this->plugin_settings->get_values('itad_cookie');
            $this->actionInit();
        }

        /**
         *
         */
        public function actionInit()
        {
            $this->saveRemoteHtml();
            $this->parseCacheHtml();
        }

        /**
         * @return void
         */
        private function saveRemoteHtml()
        {
            $queries = $this->plugin_settings->get_values('queries');
            $this->home_html = $this->getRemoteHomeHtml();

            foreach ($queries as $index => $query) {
                $cached_data = get_transient($query['query_key']);
                if (!$cached_data) {
                    $html_game_list = $this->getRemoteGameList(
                        fw_akg('query_value', $query, false)
                    );
                    if ($html_game_list) {
                        set_transient($query['query_key'], $html_game_list, DAY_IN_SECONDS / 4);
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

        /**
         * @return string
         */
        private function getRemoteHomeHtml()
        {
            $this->curl->setHeader(
                'cookie',
                $this->cookie
            );
            $this->curl->setCookie('region', 'tr');
            $data = $this->curl->get(self::$statics['url']['home']);

            return $data->response;
        }

        /**
         * @param $query
         *
         * @return bool
         */
        private function getRemoteGameList($query)
        {
            $params = [
                'offset' => 0,
                'limit' => 500,
                'seen' => time() - 50,
                'id' => $this->getLazyId(),
                'timestamp' => time(),
                'options' => '',
                'by' => 'price:asc',
                'filter' => $query,
            ];

            $this->curl->setHeader(
                'accept',
                'application/json, text/javascript, */*; q=0.01'
            );
            $this->curl->setHeader('referer', 'https://isthereanydeal.com/');
            $this->curl->setHeader('origin', 'https://isthereanydeal.com');
            $post = $this->curl->post(
                self::$statics['url']['lazy-deals'],
                $params
            );
            $json = json_decode($post->response);
            if ($json->status === 'success') {
                return $json->data->html;
            }

            return false;
        }

        /**
         * @return string
         */
        private function getLazyId()
        {
            if (!$this->home_html) {
                $this->getRemoteHomeHtml();
            }

            $xpath = $this->getXpath($this->home_html);
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
         * @param $data
         *
         * @return \DOMXPath
         */
        private function getXpath($data)
        {
            /* Use internal libxml errors -- turn on in production, off for debugging */
            libxml_use_internal_errors(true);

            $data = mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8');
            /* Createa a new DomDocument object */
            $dom = new \DOMDocument();
            /* Load the HTML */

            $dom->loadHTML($data);
            $dom->preserveWhiteSpace = false;

            $dom->saveHTML();

            /* Create a new XPath object */
            $xpath = new \DOMXPath($dom);

            return $xpath;
        }

        /**
         * @param $cache
         *
         * @return array|bool
         */
        private function parseCacheHtml()
        {
            $data = [];


            if ($this->cached) {
                foreach ($this->cached as $index => $item) {
                    $xpath = $this->getXpath($item['cache']);

                    $nodes = $xpath->query(
                        '//*[contains(@class, "game") and contains(@class, "seen")]'
                    );

                    if (isset($nodes) && $nodes->length > 0) {
                        foreach ($nodes as $index_nodes => $node) {
                            $name = '';
                            $price = '';
                            $cut = '';
                            $url = '';

                            $nameXpath = $xpath->query('//*[@class="noticeable"]', $node);

                            if ($nameXpath->item($index_nodes)) {
                                $name = $nameXpath->item($index_nodes)->nodeValue;

                                $priceXpath = $xpath->query(
                                    'descendant::*[contains(@class, "shopMark") and contains(@class, "s-low")' .
                                    ' and contains(@class, "steam")]',
                                    $node
                                );

                                if ($priceXpath->item(0) === null) {
                                    return false;
                                }

                                $price = floatval(
                                    preg_replace(
                                        '/[^-0-9\.]/',
                                        '.',
                                        $this->getNodeValue($priceXpath)
                                    )
                                );

                                $url = untrailingslashit(
                                    $priceXpath->item(0)->getAttribute('href')
                                );

                                $cutXpath = $xpath->query(
                                    'descendant::*[contains(@class, "details")]/a[contains(@class,' .
                                    ' "steam")]//*[contains(@class, "cut")]',
                                    $node
                                );

                                $cut = $this->getNodeValue($cutXpath);

                                if (
                                    isset($name) && !empty($name) && !empty($cut)
                                    && !empty($price) && !empty($url)
                                ) {
                                    $game = \Rambouillet\Util\Medoo::insertNotExists(
                                        'games',
                                        [
                                            'name' => $name,
                                            'url' => $url,
                                        ]
                                    );

                                    $price = \Rambouillet\Util\Medoo::insertNotExists(
                                        'prices',
                                        [
                                            'game_id' => (int)$game['ID'],
                                            'price' => (double)$price,
                                            'cut' => (int)$cut,
                                        ]
                                    );

                                    \Rambouillet\Util\Medoo::insertNotExists(
                                        'rambouillet_posts',
                                        [
                                            'price_id' => (int)$price['ID'],
                                        ]
                                    );
                                }
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

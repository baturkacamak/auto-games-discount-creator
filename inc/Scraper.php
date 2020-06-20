<?php

namespace Rambouillet;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Rambouillet\Unyson\PluginSettings;
use Rambouillet\Utility\Helper;

if (!class_exists('Rambouillet\Scraper')) {
    /**
     * Class Scrape
     *
     * @package Rambouillet
     */
    class Scraper
    {
        /**
         * @var array
         */
        private $data = [];
        /**
         * @var array
         */
        private $queryResults = [];
        /**
         * @var PluginSettings
         */
        private $pluginSettings;
        /**
         * @var Client
         */
        private $guzzle;
        /**
         * @var string
         */
        private $type;
        /**
         * @var string
         */
        protected $homeHtml;
        /**
         * @var array|Settings
         */
        private $settings;

        /**
         * Scrape constructor.
         * @param string $type
         * @param array|Settings $settings
         * @throws GuzzleException
         */
        public function __construct($type = 'daily', $settings = [])
        {
            $this->type = $type;
            $this->settings = $settings;
            $this->setGuzzle();
        }

        /**
         * @param Client $guzzle
         */
        public function setGuzzle(Client $guzzle = null)
        {
            $base_url = $this->settings->get_values('base_url', []);

            $cookie_jar = CookieJar::fromArray(
                ['region' => 'tr', 'country' => 'TR%3ASpain'],
                parse_url($base_url)['host']
            );

            if (!$guzzle) {
                $guzzle = new Client(
                    [
                        'base_uri' => $base_url,
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36' .
                                ' (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36',
                        ],
                        'cookies' => $cookie_jar
                    ]
                );
            }

            $this->guzzle = $guzzle;
        }

        /**
         * @param string $homeHtml
         * @throws GuzzleException
         */
        protected function setHomeHtml(string $homeHtml = '')
        {
            if (empty($homeHtml)) {
                $home_html = $this->guzzle->request('GET')->getBody()->getContents();
            } else {
                $home_html = $homeHtml;
            }

            $this->homeHtml = $home_html;
        }


        /**
         * @return array
         * @throws GuzzleException
         */
        public function getQueryResults()
        {
            $queries = $this->settings->get_values('queries');
            $transient_time = DAY_IN_SECONDS / 4;

            foreach ($queries as $index => $query) {
                if ($this->type === $query['query_type']) {
                    $transient_data = get_transient($query['query_key']);

                    if (
                        !$transient_data && $query_result = $this->getQueryResult(
                            fw_akg('query_value', $query, false)
                        )
                    ) {
                        if ('hourly' === $query['query_type']) {
                            $transient_time = HOUR_IN_SECONDS;
                        }
                        $transient_data = $query_result;
                        set_transient($query['query_key'], $transient_data, $transient_time);
                    }

                    if ($transient_data) {
                        $this->queryResults[] = [
                            'cache_type' => $query['query_key'],
                            'cache' => $transient_data,
                        ];
                    }
                }
            }
            return $this->queryResults;
        }

        /**
         * @param $filter
         *
         * @return bool
         * @throws \GuzzleHttp\Exception\GuzzleException
         */
        public function getQueryResult($filter)
        {
            $form_params = [
                'offset' => 0,
                'limit' => 100,
                'seen' => time() - 3000,
                'id' => (int)$this->getRemoteLazyId(),
                'timestamp' => time(),
                'options' => 'strict',
                'by' => 'price:asc',
                'filter' => $filter,
            ];

            try {
                $response = $this->guzzle->request(
                    'POST',
                    $this->settings->get_values('lazy_deals'),
                    ['form_params' => $form_params,]
                );

                if ($response->getStatusCode() === 200) {
                    $json_remote = json_decode($response->getBody()->getContents());
                    if ($json_remote && $json_remote->status === 'success') {
                        return $json_remote->data->html;
                    }
                }
                return false;
            } catch (GuzzleException $guzzle_exception) {
                throw $guzzle_exception;
            }
        }

        /**
         * @return string
         * @throws GuzzleException
         */
        protected function getRemoteLazyId()
        {
            if (!$this->homeHtml) {
                $this->setHomeHtml();
            }

            $xpath = Helper::getXpath($this->homeHtml);
            /* Query all <td> nodes containing specified class name */
            $script_nodes = $xpath->query("//script");

            $lazy_id = '';
            foreach ($script_nodes as $index => $script_node) {
                preg_match_all('/id:\s*"([a-zA-Z0-9]+)"/', $script_node->nodeValue, $matches);
                if (isset($matches[1][0]) && !empty($matches[1][0])) {
                    $lazy_id = $matches[1][0];
                    break;
                }
            }

            // LazyLoad ID
            return $lazy_id;
        }
    }
}

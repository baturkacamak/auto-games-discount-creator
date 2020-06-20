<?php

namespace Rambouillet;

use Exception;
use Rambouillet\Utility\Helper;

if (!class_exists('Rambouillet\Parser')) {
    /**
     * Class Parser
     *
     * @package Rambouillet
     */
    class Parser
    {
        /**
         * @var array
         */
        private $queryResults;

        /**
         * @param $queryResults
         * @return void
         */
        public function setQueryResults($queryResults)
        {
            $this->queryResults = $queryResults;
        }

        /**
         * @return array|bool
         * @throws Exception
         */
        public function parseQueryResults()
        {
            if ($this->queryResults && is_array($this->queryResults)) {
                foreach ($this->queryResults as $index => $query_result) {
                    if (!isset($query_result['cache'])) {
                        throw new Exception('cache is not set');
                    }

                    $xpath = Helper::getXpath($query_result['cache']);

                    $game_nodes = $xpath->query(
                        '//*[contains(@class, "game") and contains(@class, "seen")]'
                    );

                    if (isset($game_nodes) && $game_nodes->length > 0) {
                        foreach ($game_nodes as $index_nodes => $game_node) {
                            $game_std = new \stdClass();

                            $name_xpath = $xpath->query('//*[@class="noticeable"]', $game_node);

                            if ($name_xpath->item($index_nodes)) {
                                $game_std->name = $name_xpath->item($index_nodes)->nodeValue;

                                $price_xpath = $xpath->query(
                                    'descendant::*[contains(@class, "shopMark") and contains(@class, "g-low")]',
                                    $game_node
                                );

                                if (null === $price_xpath->item(0)) {
                                    return false;
                                }
                                $game_std->price = floatval(
                                    preg_replace(
                                        '/[^-0-9\.]/',
                                        '.',
                                        $this->getNodeValue($price_xpath)
                                    )
                                );
                                $game_std->url = untrailingslashit(
                                    $price_xpath->item(0)->getAttribute('href')
                                );
                                $cut_xpath = $xpath->query(
                                    'descendant::*[contains(@class, "details")]/a//*[contains(@class, "cut")]',
                                    $game_node
                                );
                                $game_std->cut = $this->getNodeValue($cut_xpath);
                                if (
                                    isset($game_std->name) && !empty($game_std->name)
                                    && !empty($game_std->cut) && !empty($game_std->url)
                                ) {
                                    $game_medoo = \Rambouillet\Utility\Medoo::insertNotExists(
                                        'games',
                                        [
                                            'name' => $game_std->name,
                                            'url' => $game_std->url,
                                        ]
                                    );

                                    $price_medoo = \Rambouillet\Utility\Medoo::insertNotExists(
                                        'prices',
                                        [
                                            'game_id' => (int)$game_medoo['ID'],
                                            'price' => (double)$game_std->price,
                                            'cut' => (int)$game_std->cut,
                                        ]
                                    );

                                    if (is_array($price_medoo)) {
                                        \Rambouillet\Utility\Medoo::insertNotExists(
                                            'rambouillet_posts',
                                            [
                                                'price_id' => (int)$price_medoo['ID'],
                                            ]
                                        );
                                    }
                                }
                                unset($game_std);
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

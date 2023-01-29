<?php

namespace Rambouillet;

use DOMNode;
use DOMXPath;
use Exception;
use Rambouillet\Utility\Helper;
use Rambouillet\Utility\Medoo;
use stdClass;

if (!class_exists('Rambouillet\Parser')) {
	/**
	 * Class Parser
	 *
	 * This class is used to parse query results and extract information from them.
	 *
	 * @package Rambouillet
	 */
	class Parser
	{
		/**
		 * @var array $queryResults The results of a query.
		 */
		private $queryResults;

		/**
		 * @var DOMNodeHandler $domHandler An instance of the DOMNodeHandler class.
		 */
		private $domHandler;

		/**
		 * Parser constructor.
		 *
		 * Creates a new instance of the Parser class and instantiates a new DOMNodeHandler.
		 *
		 * @param array $queryResults The results of a query.
		 */
		public function __construct($queryResults)
		{
			$this->domHandler = new DOMNodeHandler();
			$this->queryResults = $queryResults;
		}

		/**
		 * Parses the query results and checks if the "cache" element is set.
		 *
		 * @throws Exception Throws an exception if the "cache" element is not set in a query result.
		 */
		public function parseQueryResults(): void
		{
			if (empty($this->queryResults)) {
				return;
			}

			foreach ($this->queryResults as $this->queryResults) {
				if (!array_key_exists('cache', $this->queryResults)) {
					throw new Exception('Cache element not found in query result.');
				}

				$this->parseCacheResult($this->queryResults['cache']);
			}
		}

		/**
		 * Parses the cache result and inserts game information into the database.
		 *
		 * @param string $cache A string representation of the cache result.
		 *
		 * @return void
		 * @throws Exception If the xpath query or insertion into the database fails.
		 */
		public function parseCacheResult($cache): void
		{
			$xpath = $this->domHandler->createXpathFromHtml($cache);

			$game_nodes = $this->domHandler->handleXPathQuery(
				$xpath,
				'//*[contains(@class, "game") and contains(@class, "seen")]'
			);

			if (isset($game_nodes) && $this->domHandler->checkNodeExists($game_nodes)) {
				foreach ($game_nodes as $index_nodes => $game_node) {
					$game_info = new stdClass();
					$game_info = $this->parseGameNode($xpath, $game_info, $game_node, $index_nodes);
					$this->insertIntoDatabase($game_info);
					unset($game_info);
				}
			}
		}

		/**
		 * Parses a node result.
		 *
		 * @param DOMXPath $xpath An instance of the DOMXPath class.
		 * @param stdClass $gameInfo An instance of the stdClass class.
		 * @param DOMNode $gameNode A DOM node.
		 *
		 * @return stdClass
		 */
		public function parseGameNode($xpath, $gameInfo, $gameNode, $indexNodes)
		{
			$name_xpath = $this->domHandler->handleXPathQuery(
				$xpath,
				'//*[@class="noticeable"]',
				$gameNode
			);

			if ($name_xpath->item($indexNodes)) {
				$gameInfo->name = $this->domHandler->getDomNodeValue($name_xpath->item($indexNodes));

				$price_xpath = $this->domHandler->handleXPathQuery(
					$xpath,
					'descendant::*[contains(@class, "shopMark") and contains(@class, "g-low")]',
					$gameNode
				);

				if ($this->domHandler->checkNodeExists($price_xpath->item(0))) {
					return false;
				}

				$gameInfo->price = floatval(
					preg_replace(
						'/[^-0-9\.]/',
						'.',
						$this->domHandler->getXpathNodeValue($price_xpath)
					)
				);
				$gameInfo->url   = untrailingslashit(
					$this->domHandler->getNodeAttribute($price_xpath->item(0), 'href')
				);
				$cut_xpath      = $this->domHandler->handleXPathQuery(
					$xpath,
					'descendant::*[contains(@class, "details")]/a//*[contains(@class, "cut")]',
					$gameNode
				);

				$gameInfo->cut = $this->domHandler->getXpathNodeValue($cut_xpath);

				return $gameInfo;
			}
		}

		/**
		 * Inserts data into a database.
		 *
		 * @param stdClass $gameInfo An instance of the stdClass class.
		 *
		 * @return void
		 * @throws Exception
		 */
		public function insertIntoDatabase($gameInfo): void
		{
			if (
				isset($gameInfo->name) && !empty($gameInfo->name)
				&& !empty($gameInfo->cut) && !empty($gameInfo->url)
			) {
				$game_medoo = Medoo::insertNotExists(
					'games',
					[
						'name' => $gameInfo->name,
						'url'  => $gameInfo->url,
					]
				);

				$price_medoo = Medoo::insertNotExists(
					'prices',
					[
						'game_id' => (int)$game_medoo['ID'],
						'price'   => (double)$gameInfo->price,
						'cut'     => (int)$gameInfo->cut,
					]
				);

				if (is_array($price_medoo)) {
					Medoo::insertNotExists(
						'rambouillet_posts',
						[
							'price_id' => (int)$price_medoo['ID'],
						]
					);
				}
			}
		}
	}
}

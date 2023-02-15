<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use DOMNode;
use DOMXPath;
use Exception;
use stdClass;

if (!class_exists('AutoGamesDiscountCreator\GameDataParser')) {
	/**
	 * Class GameDataParser
	 *
	 * This class is used to parse query results and extract information from them.
	 *
	 * @package AutoGamesDiscountCreator
	 */
	class GameDataParser
	{
		/**
		 * @var DOMHandler $domHandler An instance of the DOMNodeHandler class.
		 */
		private $domHandler;

		public function __construct()
		{
			$this->domHandler = new DOMHandler();
		}

		/**
		 * Parses the cache result and returns an array of game information.
		 *
		 * @param string $cache A string representation of the cache result.
		 *
		 * @return array
		 */
		public function parseCacheResult($cache): array
		{
			$xpath      = $this->domHandler->createXpathFromHtml($cache['cache']);
			$game_nodes = $this->getGameNodes($xpath);

			$game_information = [];
			if (isset($game_nodes) && $this->domHandler->checkNodeExists($game_nodes)) {
				foreach ($game_nodes as $index_nodes => $game_node) {
					$game_info = $this->parseGameNode($xpath, $game_node, $index_nodes);
					if ($game_info) {
						$game_information[] = $game_info;
						unset($game_info);
					}
				}
			}

			return $game_information;
		}

		/**
		 * Get game nodes from the cache result.
		 *
		 * @param DOMXPath $xpath An instance of the DOMXPath class.
		 *
		 * @return DOMNodeList A list of game nodes.
		 */
		private function getGameNodes($xpath)
		{
			return $this->domHandler->handleXPathQuery(
				$xpath,
				'//*[contains(@class, "game") and contains(@class, "seen")]'
			);
		}

		/**
		 * Parses a single game node from the cache result.
		 *
		 * @param DOMXPath $xpath An instance of the DOMXPath class.
		 * @param DOMNode $gameNode A single game node.
		 * @param int $indexNodes The index of the game node in the list of game nodes.
		 *
		 * @return array An array containing the name, price, URL, and discount of a game.
		 */
		private function parseGameNode($xpath, $gameNode, $indexNodes)
		{
			$game_info = [];
			$name_node = $this->getNameNode($xpath, $gameNode, $indexNodes);

			if (!$name_node) {
				return null;
			}

			$game_info['name'] = $this->domHandler->getDomNodeValue($name_node);
			$price_node        = $this->getPriceNode($xpath, $gameNode);

			if (!$price_node) {
				return null;
			}

			$game_info['price'] = $this->parsePriceNode($price_node);
			$game_info['url']   = untrailingslashit(
				$this->domHandler->getNodeAttribute($price_node, 'href')
			);
			$cut_node           = $this->getCutNode($xpath, $gameNode);

			$game_info['cut'] = $this->domHandler->getDomNodeValue($cut_node);

			return $game_info;
		}

		/**
		 * Get the name node of a game from the cache result.
		 *
		 * @param DOMXPath $xpath An instance of the DOMXPath class.
		 * @param DOMNode $gameNode A single game node.
		 * @param int $indexNodes The index of the game node in the list of game nodes.
		 *
		 * @return DOMNode The name node of the game.
		 */
		private function getNameNode($xpath, $gameNode, $indexNodes)
		{
			return $this->domHandler->handleXPathQuery(
				$xpath,
				'//*[@class="noticeable"]',
				$gameNode
			)->item($indexNodes);
		}

		/**
		 * Get the price node of a game from the cache result.
		 *
		 * @param DOMXPath $xpath An instance of the DOMXPath class.
		 * @param DOMNode $gameNode A single game node.
		 *
		 * @return DOMNode The price node of the game.
		 */
		private function getPriceNode($xpath, $gameNode)
		{
			return $this->domHandler->handleXPathQuery(
				$xpath,
				'descendant::*[contains(@class, "shopMark") and contains(@class, "g-low")]',
				$gameNode
			)->item(0);
		}

		/**
		 * Get the discount node of a game from the cache result.
		 *
		 * @param DOMXPath $xpath An instance of the DOMXPath class.
		 * @param DOMNode $gameNode A single game node.
		 *
		 * @return DOMNode The discount node of the game.
		 */
		private function getCutNode($xpath, $gameNode)
		{
			return $this->domHandler->handleXPathQuery(
				$xpath,
				'descendant::*[contains(@class, "details")]/a//*[contains(@class, "cut")]',
				$gameNode
			)->item(0);
		}

		/**
		 * Parses the price node of a game from the cache result.
		 *
		 * @param DOMNode $priceNode The price node of the game.
		 *
		 * @return float The parsed price of the game.
		 */
		private function parsePriceNode($priceNode)
		{
			return floatval(
				preg_replace(
					'/[^-0-9\.]/',
					'.',
					$this->domHandler->getDomNodeValue($priceNode)
				)
			);
		}
	}
}

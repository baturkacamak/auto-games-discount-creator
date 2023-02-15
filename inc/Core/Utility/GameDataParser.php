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
		 * @param DOMXPath $xpath The instance of the DOMXPath class.
		 *
		 * @return DOMNodeList Returns a list of DOMNode elements matching the xpath query.
		 */
		private function getGameNodes($xpath)
		{
			return $this->domHandler->handleXPathQuery(
				$xpath,
				'//*[contains(@class, "game") and contains(@class, "seen")]'
			);
		}

		/**
		 * @param DOMXPath $xpath The instance of the DOMXPath class.
		 * @param DOMNode $gameNode The DOMNode of the game information.
		 * @param int $indexNodes The index of the DOMNode in the DOMNodeList.
		 *
		 * @return stdClass|null Returns an object containing information of the game or null if the information cannot be parsed.
		 */
		private function parseGameNode($xpath, $gameNode, $indexNodes)
		{
			$gameInfo = new stdClass();
			$nameNode = $this->getNameNode($xpath, $gameNode, $indexNodes);

			if (!$nameNode) {
				return null;
			}

			$gameInfo->name = $this->domHandler->getDomNodeValue($nameNode);
			$priceNode      = $this->getPriceNode($xpath, $gameNode);

			if (!$priceNode) {
				return null;
			}

			$gameInfo->price = $this->parsePriceNode($priceNode);
			$gameInfo->url   = untrailingslashit(
				$this->domHandler->getNodeAttribute($priceNode, 'href')
			);
			$cutNode         = $this->getCutNode($xpath, $gameNode);

			$gameInfo->cut = $this->domHandler->getDomNodeValue($cutNode);

			return $gameInfo;
		}

		/**
		 * @param DOMXPath $xpath The instance of the DOMXPath class.
		 * @param DOMNode $gameNode The DOMNode of the game information.
		 * @param int $indexNodes The index of the DOMNode in the DOMNodeList.
		 *
		 * @return DOMNode|null Returns the DOMNode containing the name of the game or null if the node cannot be found.
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
		 * @param DOMXPath $xpath The instance of the DOMXPath class.
		 * @param DOMNode $gameNode The DOMNode of the game information.
		 *
		 * @return DOMNode|null Returns the DOMNode containing the price of the game or null if the node cannot be found.
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
		 * @param DOMXPath $xpath The instance of the DOMXPath class.
		 * @param DOMNode $gameNode The DOMNode of the game information.
		 *
		 * @return DOMNode|null Returns the DOMNode containing the discount information of the game or null if the node cannot be found.
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
		 * @param DOMNode $priceNode The DOMNode containing the price information.
		 *
		 * @return float Returns the price of the game as a floating-point number.
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

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

		private function getGameNodes($xpath)
		{
			return $this->domHandler->handleXPathQuery(
				$xpath,
				'//*[contains(@class, "game") and contains(@class, "seen")]'
			);
		}

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

		private function getNameNode($xpath, $gameNode, $indexNodes)
		{
			return $this->domHandler->handleXPathQuery(
				$xpath,
				'//*[@class="noticeable"]',
				$gameNode
			)->item($indexNodes);
		}

		private function getPriceNode($xpath, $gameNode)
		{
			return $this->domHandler->handleXPathQuery(
				$xpath,
				'descendant::*[contains(@class, "shopMark") and contains(@class, "g-low")]',
				$gameNode
			)->item(0);
		}

		private function getCutNode($xpath, $gameNode)
		{
			return $this->domHandler->handleXPathQuery(
				$xpath,
				'descendant::*[contains(@class, "details")]/a//*[contains(@class, "cut")]',
				$gameNode
			)->item(0);
		}

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

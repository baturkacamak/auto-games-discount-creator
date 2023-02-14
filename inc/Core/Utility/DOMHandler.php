<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 28/1/23
 * Time: 21:08
 */


namespace AutoGamesDiscountCreator\Core\Utility;

use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Exception;
use AutoGamesDiscountCreator\Utility\Helper;

if (!class_exists('AutoGamesDiscountCreator\DOMNodeHandler')) {
	/**
	 * Class DOMNodeHandler
	 *
	 * @package AutoGamesDiscountCreator
	 */
	class DOMHandler
	{
		/**
		 * Get the value of a node from an xpath query
		 *
		 * @param DOMXPath $xpath The xpath query to execute
		 *
		 * @return mixed The value of the node, or null if the query returned no results
		 */
		public function getXpathNodeValue($xpath = null)
		{
			if (isset($xpath) && $xpath->length > 0) {
				foreach ($xpath as $index => $item) {
					return $item->nodeValue;
				}
			}
		}

		/**
		 * Get the value of a DOMNode
		 *
		 * @param DOMNode $node The node to get the value from
		 *
		 * @return mixed The value of the node
		 */
		public function getDomNodeValue(DOMNode $node)
		{
			return $node->nodeValue;
		}


		/**
		 * Get the attribute of the node
		 *
		 * @param DOMNode $node
		 * @param string $attribute
		 *
		 * @return mixed
		 */
		public function getNodeAttribute(DOMNode $node, string $attribute)
		{
			return $node->getAttribute($attribute);
		}

		/**
		 * Check if the node has a specific class
		 *
		 * @param DOMNode $node
		 * @param string $class
		 *
		 * @return bool
		 */
		public function hasNodeClass(DOMNode $node, string $class)
		{
			return in_array($class, explode(' ', $node->getAttribute('class')));
		}

		/**
		 * Get the first child node with a specific tag name
		 *
		 * @param DOMNode $node
		 * @param string $tagName
		 *
		 * @return DOMHandler|null
		 */
		public function getFirstChildByTagName(DOMNode $node, string $tagName)
		{
			$childNode = $node->getElementsByTagName($tagName)->item(0);
			if ($childNode) {
				return new DOMHandler($childNode);
			}

			return null;
		}

		/**
		 * Get the first child node with a specific class
		 *
		 * @param DOMNode $node
		 * @param string $class
		 *
		 * @return DOMHandler|null
		 */
		public function getFirstChildByClass(DOMNode $node, string $class)
		{
			$xpath     = new \DOMXPath($node->ownerDocument);
			$childNode = $xpath->query('./*[contains(@class, "' . $class . '")]', $node)->item(0);
			if ($childNode) {
				return new DOMHandler($childNode);
			}

			return null;
		}

		/**
		 * Handle a query on a given DOMXPath object
		 *
		 * @param DOMXPath $domXPath The DOMXPath object to perform the query on
		 * @param string $query The query to be executed on the DOMXPath object
		 * @param DOMNode $node The specific node to query
		 *
		 * @return DOMNodeList The nodes returned from the query
		 */
		public function handleXPathQuery(DOMXPath $domXPath, string $query, DOMNode $node = null)
		{
			if ($node) {
				return $domXPath->query($query, $node);
			}

			return $domXPath->query($query);
		}


		/**
		 * Get all child nodes with a specific class
		 *
		 * @param DOMNode $node
		 * @param string $class
		 *
		 * @return array
		 */
		public function getAllChildrenByClass(DOMNode $node, string $class)
		{
			$xpath       = new \DOMXPath($node->ownerDocument);
			$child_nodes = $this->handleXPathQuery($xpath, './*[contains(@class, "' . $class . '")]', $node);
			$children    = [];
			foreach ($child_nodes as $child_node) {
				$children[] = new DOMHandler($child_node);
			}

			return $children;
		}

		/**
		 * Removes a given DOMNode from the document
		 *
		 * @param DOMNode $node The node to be removed
		 *
		 * @return void
		 */
		public function removeNode(DOMNode $node)
		{
			$node->parentNode->removeChild($node);
		}

		/**
		 * Returns the parent node of a given DOMNode
		 *
		 * @param DOMNode $node The node to get the parent of
		 *
		 * @return DOMNode|null The parent node or null if the node has no parent
		 */
		public function getParentNode(DOMNode $node)
		{
			return $node->parentNode;
		}

		/**
		 * Returns the text content of a given DOMNode
		 *
		 * @param DOMNode $node The node to get the text content of
		 *
		 * @return string The text content of the node
		 */
		public function getTextContent(DOMNode $node)
		{
			return $node->textContent;
		}

		/**
		 * Retrieves the HTML content of a DOMNode.
		 *
		 * @param DOMNode $node The node to retrieve the HTML content from.
		 *
		 * @return string The HTML content of the node.
		 */
		public function getHTMLContent(DOMNode $node)
		{
			return $node->ownerDocument->saveHTML($node);
		}

		/**
		 * Checks if a given node or list of nodes exists in the DOM
		 *
		 * @param mixed $nodes The node or list of nodes to check for existence
		 *
		 * @return bool True if the node(s) exists, false otherwise
		 */
		public function checkNodeExists($nodes)
		{
			if (!$nodes) {
				return false;
			}

			if ($nodes instanceof DOMNodeList) {
				return $nodes->length > 0;
			}

			if ($nodes instanceof DOMNode) {
				return true;
			}

			return false;
		}

		/**
		 * Create a new DOMXPath object from a string of HTML data
		 *
		 * @param string $htmlString The HTML data to convert
		 *
		 * @return DOMXPath
		 */
		public function createXpathFromHtml($htmlString)
		{
			// Use internal libxml errors for production, turn off for debugging
			libxml_use_internal_errors(true);

			// Convert the data to HTML-ENTITIES encoding
			$converted_string = mb_convert_encoding($htmlString, 'HTML-ENTITIES', 'UTF-8');

			// Create a new DomDocument object
			$html_dom = new \DOMDocument();

			// Load the HTML
			$html_dom->loadHTML($converted_string);
			$html_dom->preserveWhiteSpace = false;

			// Save the HTML
			$html_dom->saveHTML();

			// Create a new XPath object
			return new DOMXPath($html_dom);
		}
	}
}

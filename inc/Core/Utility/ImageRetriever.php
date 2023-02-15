<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use AutoGamesDiscountCreator\DOMNodeHandler;

/**
 * Class ImageRetriever
 *
 * This class is responsible for retrieving the remote images from a given URL.
 *
 * @package AutoGamesDiscountCreator\Core\Utility
 */
class ImageRetriever
{
	/**
	 * @var WebClient
	 */
	private $webClient;

	/**
	 * @var DOMHandler
	 */
	private $domHandler;

	/**
	 * ImageRetriever constructor.
	 *
	 * Initializes the WebClient and DOMNodeHandler instances.
	 */
	public function __construct()
	{
		$this->webClient  = new WebClient();
		$this->domHandler = new DOMHandler();
	}

	/**
	 * Retrieves the remote image from the given URL.
	 *
	 * @param string $url The URL to retrieve the remote image from.
	 *
	 * @return string The URL of the remote image.
	 */
	public function retrieve(string $url): string
	{
		// Get the HTML response from the URL
		$html_response = $this->webClient->get($url);

		// If the HTML response was successful
		if ($html_response) {
			// Create the XPath object from the HTML response
			$xpath           = $this->domHandler->createXpathFromHtml($html_response);

			// Get the meta image node from the XPath object
			$meta_image_node = $xpath->query('//meta[@property="og:image"]');

			// If the meta image node exists
			if ($meta_image_node && $meta_image_node->length) {
				// Return the content attribute of the meta image node
				return $meta_image_node->item(0)->getAttribute('content') ? $meta_image_node->item(0)->getAttribute(
					'content'
				) : '';
			}
		}

		return '';
	}

}

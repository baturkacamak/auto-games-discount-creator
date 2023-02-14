<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use AutoGamesDiscountCreator\DOMNodeHandler;

/**
 * Class Web
 *
 * This class is responsible for making web requests and parsing the responses.
 *
 * @package AutoGamesDiscountCreator\Core\Utility
 */
class Web
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
	 * Web constructor.
	 *
	 * Initializes the WebClient and DOMNodeHandler instances.
	 */
	public function __construct()
	{
		$this->webClient  = new WebClient();
		$this->domHandler = new DOMHandler();
	}

	/**
	 * Gets the remote image from the given URL.
	 *
	 * @param string $url The URL to make the request to.
	 *
	 * @return string The URL of the remote image.
	 */
	public function getRemoteImage(string $url): string
	{
		$html_response = $this->webClient->get($url);
		if ($html_response) {
			$xpath           = $this->domHandler->createXpathFromHtml($html_response);
			$meta_image_node = $xpath->query('//meta[@property="og:image"]');
			if ($meta_image_node && $meta_image_node->length) {
				return $meta_image_node->item(0)->getAttribute('content') ? $meta_image_node->item(0)->getAttribute(
					'content'
				) : '';
			}
		}

		return '';
	}

}

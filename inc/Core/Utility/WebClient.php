<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 19:13
 */

namespace AutoGamesDiscountCreator\Core\Utility;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class WebClient
{
	/**
	 * @var Client
	 */
	public $client;

	public function __construct($configuration = [])
	{
		$this->client = new Client(
			array_merge([
				'headers' => [
					'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36' .
					                ' (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36',
				],
			], $configuration)
		);
	}

	/**
	 * Makes a GET request to the given URL and returns the response body.
	 *
	 * @param string $url The URL to make the request to.
	 *
	 * @return string The response body.
	 * @throws GuzzleException
	 */
	public function get(string $url = ''): string
	{
		if (empty($url)) {
			return $this->client->request('GET')->getBody()->getContents();
		}

		$response = $this->client->get($url);
		if (200 === $response->getStatusCode()) {
			return $response->getBody()->getContents();
		}


		return '';
	}

	/**
	 * Makes a POST request to the given URL with the specified data and returns the response body.
	 *
	 * @param string $url The URL to make the request to.
	 * @param array $data The data to send with the request.
	 *
	 * @return ResponseInterface The response body.
	 */
	public function post(string $url = '', array $data = []): ResponseInterface
	{
		return $this->client->request('POST', $url, ['form_params' => $data]);
	}

	/**
	 * @return Client
	 */
	public function getClient(): Client
	{
		return $this->client;
	}
}

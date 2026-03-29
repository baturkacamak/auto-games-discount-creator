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

	public function getUntilMatch(string $url, string $pattern, int $maxBytes = 131072): string
	{
		if ($url === '') {
			return '';
		}

		$response = $this->client->request(
			'GET',
			$url,
			[
				'stream' => true,
				'http_errors' => false,
				'headers' => [
					'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				],
			]
		);

		if ($response->getStatusCode() !== 200) {
			return '';
		}

		$body = $response->getBody();
		$buffer = '';

		try {
			while (!$body->eof() && strlen($buffer) < $maxBytes) {
				$chunk = $body->read(min(8192, $maxBytes - strlen($buffer)));
				if ($chunk === '') {
					break;
				}

				$buffer .= $chunk;

				if (preg_match($pattern, $buffer) === 1) {
					break;
				}
			}
		} finally {
			$body->close();
		}

		return $buffer;
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

	public function postJson(string $url = '', array $data = []): ResponseInterface
	{
		$default_headers = $this->client->getConfig('headers');
		if (!is_array($default_headers)) {
			$default_headers = [];
		}

		return $this->client->request(
			'POST',
			$url,
			[
				'headers' => array_merge(
					$default_headers,
					[
						'Accept' => 'application/json',
						'Content-Type' => 'application/json',
					]
				),
				'json' => $data,
			]
		);
	}

	public function resolveFinalUrl(string $url): string
	{
		if ($url === '') {
			return '';
		}

		$effective_url = $url;
		$redirect_history = [];
		$response = $this->client->request(
			'GET',
			$url,
			[
				'allow_redirects' => [
					'max' => 10,
					'track_redirects' => true,
				],
				'http_errors' => false,
				'on_stats' => static function ($stats) use (&$effective_url): void {
					$uri = $stats->getEffectiveUri();
					if ($uri) {
						$effective_url = (string) $uri;
					}
				},
			]
		);

		$redirect_history = $response->getHeader('X-Guzzle-Redirect-History');
		$canonical_url = $this->selectCanonicalRedirectUrl($effective_url, $redirect_history);

		return $this->stripTrackingQuery($canonical_url);
	}

	private function selectCanonicalRedirectUrl(string $effectiveUrl, array $redirectHistory): string
	{
		$candidate_urls = array_values(
			array_filter(
				array_merge($redirectHistory, [$effectiveUrl]),
				static fn($value): bool => is_string($value) && $value !== ''
			)
		);

		if ($candidate_urls === []) {
			return $effectiveUrl;
		}

		$effective_parts = parse_url($effectiveUrl);
		$effective_host = strtolower((string) ($effective_parts['host'] ?? ''));
		$effective_path = (string) ($effective_parts['path'] ?? '/');

		if ($effective_host === 'www.humblebundle.com' && rtrim($effective_path, '/') === '/store') {
			for ($index = count($candidate_urls) - 1; $index >= 0; $index--) {
				$candidate = $candidate_urls[$index];
				$parts = parse_url($candidate);
				$host = strtolower((string) ($parts['host'] ?? ''));
				$path = (string) ($parts['path'] ?? '');

				if ($host !== 'www.humblebundle.com' || $path === '') {
					continue;
				}

				if (preg_match('#^/store/agecheck/([^/?#]+)#', $path, $matches)) {
					return 'https://www.humblebundle.com/store/' . $matches[1];
				}

				if ($path !== '/store' && str_starts_with($path, '/store/')) {
					return 'https://www.humblebundle.com' . $path;
				}
			}
		}

		return $effectiveUrl;
	}

	private function stripTrackingQuery(string $url): string
	{
		$parts = parse_url($url);
		if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
			return $url;
		}

		$normalized = $parts['scheme'] . '://';
		if (!empty($parts['user'])) {
			$normalized .= $parts['user'];
			if (!empty($parts['pass'])) {
				$normalized .= ':' . $parts['pass'];
			}
			$normalized .= '@';
		}

		$normalized .= $parts['host'];
		if (!empty($parts['port'])) {
			$normalized .= ':' . $parts['port'];
		}

		$normalized .= $parts['path'] ?? '/';

		return $normalized;
	}

	/**
	 * @return Client
	 */
	public function getClient(): Client
	{
		return $this->client;
	}
}

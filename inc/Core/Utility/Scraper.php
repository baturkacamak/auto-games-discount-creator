<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use AutoGamesDiscountCreator\AutoGamesDiscountCreator;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;

if (!class_exists('AutoGamesDiscountCreator\Core\Utility\Scraper')) {
	/**
	 * Class Scrape
	 *
	 * @package AutoGamesDiscountCreator
	 */
	class Scraper
	{
		/**
		 * @var array
		 */
		private $data = [];
		/**
		 * @var array
		 */
		private $queryResults = [];
		/**
		 * @var PluginSettings
		 */
		private $pluginSettings;
		/**
		 * @var WebClient
		 */
		private $guzzle;
		/**
		 * @var string
		 */
		private $type;
		/**
		 * @var string
		 */
		protected $homeHtml;
		/**
		 * @var array|Settings
		 */
		private $settings;
		private WordPressFunctions $wpFunctions;

		public const BASE_URL = 'https://isthereanydeal.com';
		private const LAZY_DEALS = 'ajax/data/lazy.deals.php';
		private DOMHandler $domHandler;

		/**
		 * Scrape constructor.
		 *
		 * @param string $type
		 * @param array|Settings $settings
		 *
		 * @throws GuzzleException
		 */
		public function __construct(string $type = 'daily')
		{
			$this->type        = $type;
			$this->settings    = AutoGamesDiscountCreator::getInstance()->settings;
			$this->wpFunctions = new WordPressFunctions($this);
			$this->domHandler  = new DOMHandler();
			$this->setGuzzle();
		}

		private function setGuzzle()
		{
			$cookie_jar = CookieJar::fromArray(
				['currency' => 'TRY', 'country' => 'TR'],
				parse_url(self::BASE_URL)['host']
			);

			$guzzle = new WebClient(
				[
					'base_uri' => self::BASE_URL,
					'cookies'  => $cookie_jar,
				]
			);

			$this->guzzle = $guzzle;
		}

		/**
		 * @param string $homeHtml
		 *
		 * @throws GuzzleException
		 */
		protected function setHomeHtml(string $homeHtml = '')
		{
			$home_html = $homeHtml;

			if (empty($homeHtml)) {
				$home_html = $this->guzzle->get();
			}

			$this->homeHtml = $home_html;
		}


		/**
		 * @return array
		 * @throws GuzzleException
		 */
		public function getQueryResults()
		{
			$queries        = agdc_get('queries', $this->settings);
			$transient_time = DAY_IN_SECONDS / 4;

			foreach ($queries as $index => $query) {
				if ($this->type === $query['query-type']) {
					$transient_data = $this->wpFunctions->getTransient($query['query-key']);

					if (
						!$transient_data && $query_result = $this->getQueryResult(
							agdc_get('query-value', $query, false)
						)
					) {
						if ('hourly' === $query['query-type']) {
							$transient_time = HOUR_IN_SECONDS;
						}
						$transient_data = $query_result;
						$this->wpFunctions->setTransient($query['query-key'], $transient_data, $transient_time);
					}

					if ($transient_data) {
						$this->queryResults[] = [
							'cache_type' => $query['query-key'],
							'cache'      => $transient_data,
						];
					}
				}
			}

			return $this->queryResults;
		}

		/**
		 * @param $filter
		 *
		 * @return bool
		 * @throws \GuzzleHttp\Exception\GuzzleException
		 */
		public function getQueryResult($filter)
		{
			$form_params = [
				'offset'    => 0,
				'limit'     => 100,
				'seen'      => time() - 3000,
				'id'        => (int)$this->getRemoteLazyId(),
				'timestamp' => time(),
				'options'   => 'strict',
				'by'        => 'price:asc',
				'filter'    => $filter,
			];

			try {
				$response = $this->guzzle->post(
					self::LAZY_DEALS,
					$form_params
				);

				if ($response->getStatusCode() === 200) {
					$json_remote = json_decode($response->getBody()->getContents());
					if ($json_remote && $json_remote->status === 'success') {
						return $json_remote->data->html;
					}
				}

				return false;
			} catch (GuzzleException $guzzle_exception) {
				throw $guzzle_exception;
			}
		}

		/**
		 * @return string
		 * @throws GuzzleException
		 */
		protected function getRemoteLazyId()
		{
			if (!$this->homeHtml) {
				$this->setHomeHtml();
			}

			$xpath = $this->domHandler->createXpathFromHtml($this->homeHtml);
			/* Query all <td> nodes containing specified class name */
			$script_nodes = $this->domHandler->handleXPathQuery($xpath, "//script");

			$lazy_id = '';
			foreach ($script_nodes as $index => $script_node) {
				preg_match_all('/id:\s*"([a-zA-Z0-9]+)"/', $script_node->nodeValue, $matches);
				if (isset($matches[1][0]) && !empty($matches[1][0])) {
					$lazy_id = $matches[1][0];
					break;
				}
			}

			// LazyLoad ID
			return $lazy_id;
		}
	}
}

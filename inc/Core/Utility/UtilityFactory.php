<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 18/2/23
 * Time: 17:47
 */

namespace AutoGamesDiscountCreator\Core\Utility;

use AutoGamesDiscountCreator\AutoGamesDiscountCreator;
use AutoGamesDiscountCreator\Core\Utility\Image\EpicCatalogImageLookup;
use AutoGamesDiscountCreator\Core\Utility\Image\ImageUrlNormalizer;
use AutoGamesDiscountCreator\Core\Utility\Image\RemoteMetaImageStrategy;
use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\EpicFreeGamesCatalogImageStrategy;
use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\EpicStoreCdnImageStrategy;
use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\ExistingStoreCdnImageStrategy;
use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\FanaticalImageStrategy;
use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\GogImageStrategy;
use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\HumbleAgeCheckImageStrategy;
use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\HumbleStoreCdnImageStrategy;
use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\ItadAssetFallbackStrategy;
use AutoGamesDiscountCreator\Core\Utility\Image\Strategy\SteamImageStrategy;

/**
 * Class UtilityFactory
 *
 * This class is responsible for creating instances of utility classes.
 *
 * @package AutoGamesDiscountCreator\Core\Utility
 */
class UtilityFactory
{
	/**
	 * Creates an instance of the ImageRetriever class.
	 *
	 * @return ImageRetriever
	 */
	public function createImageRetriever(): ImageRetriever
	{
		return new ImageRetriever(
			new WebClient([
				'timeout' => 3,
				'connect_timeout' => 2,
				'http_errors' => false,
				'allow_redirects' => [
					'max' => 5,
				],
			]),
			new DOMHandler()
		);
	}

	public function createGameReviewLookup(): GameReviewLookup
	{
		return new GameReviewLookup(
			new WebClient([
				'timeout' => 4,
				'connect_timeout' => 2,
				'http_errors' => false,
			])
		);
	}

	public function createOfferImageResolver(): OfferImageResolver
	{
		$image_retriever = $this->createImageRetriever();
		$image_url_normalizer = new ImageUrlNormalizer();
		$settings = AutoGamesDiscountCreator::getInstance()->settings ?? [];
		$bootstrap = function_exists('get_transient') ? get_transient('agdc_itad_session_bootstrap') : [];
		$country_code = 'US';
		if (is_array($bootstrap) && !empty($bootstrap['itad_country_code'])) {
			$country_code = (string) $bootstrap['itad_country_code'];
		} elseif (!empty($settings['source']['itad_country_code'])) {
			$country_code = (string) $settings['source']['itad_country_code'];
		}

		$lookupClient = new WebClient([
			'timeout' => 4,
			'connect_timeout' => 2,
			'http_errors' => false,
		]);
		$epicLookups = [
			new EpicCatalogImageLookup($lookupClient, $image_url_normalizer, $country_code, 'en-US'),
		];
		if (strtoupper($country_code) !== 'US') {
			$epicLookups[] = new EpicCatalogImageLookup($lookupClient, $image_url_normalizer, 'US', 'en-US');
		}

		return new OfferImageResolver([
			new ExistingStoreCdnImageStrategy(),
			new SteamImageStrategy(),
			new GogImageStrategy($image_url_normalizer),
			new FanaticalImageStrategy($image_url_normalizer),
			new HumbleStoreCdnImageStrategy($image_url_normalizer),
			new HumbleAgeCheckImageStrategy(),
			new EpicFreeGamesCatalogImageStrategy($epicLookups),
			new EpicStoreCdnImageStrategy($image_url_normalizer),
			new RemoteMetaImageStrategy($image_retriever),
			new ItadAssetFallbackStrategy(),
		]);
	}
}

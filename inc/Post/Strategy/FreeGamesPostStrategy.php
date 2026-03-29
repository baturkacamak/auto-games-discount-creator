<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 14/2/23
 * Time: 18:29
 */

namespace AutoGamesDiscountCreator\Post\Strategy;

use AutoGamesDiscountCreator\Core\Settings\MarketTargetRepository;
use AutoGamesDiscountCreator\Core\Utility\Date;
use AutoGamesDiscountCreator\Core\Utility\OfferImageResolver;
use AutoGamesDiscountCreator\Core\Utility\UtilityFactory;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctionsInterface;
class FreeGamesPostStrategy implements PostTypeStrategy
{
	/**
	 * @var array $gameData An array of game data for the free game.
	 */
	private array $gameData;
	private OfferImageResolver $offerImageResolver;
	private WordPressFunctionsInterface $wordpressFunctions;
	private array $marketTarget;
	private array $copySet;

	/**
	 * FreeGamesPostStrategy constructor.
	 *
	 * @param array $gameData An array of game data for the free game.
	 * @param UtilityFactory $utilityFactory The utility factory to use for creating the ImageRetriever instance.
	 * @param WordPressFunctionsInterface $wordpressFunctions The WordPress functions instance.
	 */
	public function __construct(array $gameData, UtilityFactory $utilityFactory, WordPressFunctionsInterface $wordpressFunctions, ?array $marketTarget = null)
	{
		$this->gameData = $gameData;

		$this->offerImageResolver = $utilityFactory->createOfferImageResolver();
		$this->wordpressFunctions = $wordpressFunctions;
		$this->marketTarget = $marketTarget ?: (new MarketTargetRepository())->getDefaultTarget();
		$this->copySet = (new MarketTargetRepository())->getCopySet($this->marketTarget);
	}

	/**
	 * Returns the post title for the free game.
	 *
	 * @return string The post title for the free game.
	 */
	public function getPostTitle(): string
	{
		return sprintf(
			$this->copySet['free_title'],
			$this->gameData['name'],
			date('d'),
			$this->getLocalizedMonthName(),
			date('Y')
		);
	}

	/**
	 * Returns the game data for the free game.
	 *
	 * @param array $where An array of database query conditions.
	 *
	 * @return array The game data for the free game.
	 */
	public function getGameData(array $where): array
	{
		return [$this->gameData];
	}

	/**
	 * Determines whether a post for the free game should be created.
	 *
	 * @param array $gameData An array of game data for the free game.
	 *
	 * @return bool Whether a post for the free game should be created.
	 */
	public function shouldCreatePost(array $gameData): bool
	{
		return $gameData && count($gameData) > 0 && 0 === post_exists($this->getPostTitle());
	}

	/**
	 * Returns the post content for the free game.
	 *
	 * @param array $gameData An array of game data for the free game.
	 *
	 * @return string The post content for the free game.
	 * @throws Exception If the content template file is not found.
	 */
	public function getPostContent(array $gameData): string
	{
		$this->gameData['thumbnail_url'] = $this->offerImageResolver->resolve([
			'url' => (string) ($gameData[0]['url'] ?? ''),
			'store_key' => (string) ($gameData[0]['store_key'] ?? ''),
			'thumbnail_url' => (string) ($gameData[0]['thumbnail_url'] ?? ''),
		]);
		$this->gameData['store_key'] = $gameData[0]['store_key'] ?? ($this->gameData['store_key'] ?? 'store');
		$this->gameData['currency_code'] = $gameData[0]['currency_code'] ?? ($this->gameData['currency_code'] ?? '');
		$priceLabel = $this->copySet['price_label'] ?? 'Price';
		$storeLabel = $this->copySet['store_label'] ?? 'Store';
		$freeLabel = $this->copySet['free_price_label'] ?? 'FREE';
		$intro = sprintf($this->copySet['free_intro'] ?? 'Free game: %s', $this->gameData['name']);
		$ctaLabel = $this->copySet['cta_label'] ?? 'Open Store Page';

		$html = '<div class="steam-content-body"><p>' . esc_html($intro) . '</p></div>';
		$html .= '<p><strong>' . esc_html($this->gameData['name']) . '</strong> ' . esc_html__('şu anda kısa süreliğine ücretsiz.', 'auto-games-discount-creator') . '</p>';
		$html .= '<div class="steam-cards"><div class="ui cards free-game"><div class="ui card">';

		if (!empty($this->gameData['thumbnail_url'])) {
			$html .= '<div class="image"><a href="' . esc_url($this->gameData['url']) . '" target="_blank" rel="noopener">';
			$html .= '<img src="' . esc_url($this->gameData['thumbnail_url']) . '" alt="' . esc_attr($this->gameData['name']) . '" width="100%">';
			$html .= '</a></div>';
		}

		$html .= '<div class="content">';
		$html .= '<a class="header" href="' . esc_url($this->gameData['url']) . '" target="_blank" rel="noopener">' . esc_html($this->gameData['name']) . '</a>';
		$html .= '<div class="description">';
		$html .= '<div>' . esc_html($priceLabel) . ': <strong>' . esc_html($freeLabel) . '</strong></div>';
		$html .= '<div>' . esc_html($storeLabel) . ': <strong>' . esc_html($this->formatStoreKey((string) ($this->gameData['store_key'] ?? ''))) . '</strong></div>';
		$html .= '</div></div>';
		$html .= '<div class="extra content"><a href="' . esc_url($this->gameData['url']) . '" target="_blank" rel="noopener">' . esc_html($ctaLabel) . '</a></div>';
		$html .= '</div></div></div><div class="ui hidden divider"></div>';

		return $html;
	}

	public function getPostSlug(): string
	{
		return sanitize_title(
			sprintf(
				'%s-free-game-%s',
				$this->marketTarget['seo_path_prefix'] ?? $this->marketTarget['site_section'] ?? 'market',
				$this->gameData['name'] ?? 'deal'
			)
		);
	}

	public function getContentKind(): string
	{
		return 'free_game';
	}

	public function getMarketTarget(): array
	{
		return $this->marketTarget;
	}
	private function getLocalizedMonthName(): string
	{
		$month = (int) date('n');
		return (string) ($this->copySet['month_names'][$month] ?? date('F'));
	}

	private function formatStoreKey(string $storeKey): string
	{
		$storeKey = trim($storeKey);
		if ($storeKey === '') {
			return '';
		}

		return ucwords(str_replace(['_', '-'], ' ', $storeKey));
	}
}

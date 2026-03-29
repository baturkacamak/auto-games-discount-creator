<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 14/2/23
 * Time: 18:28
 */

namespace AutoGamesDiscountCreator\Post\Strategy;

use AutoGamesDiscountCreator\Core\Settings\MarketTargetRepository;
use AutoGamesDiscountCreator\Core\Utility\Date;
use AutoGamesDiscountCreator\Core\Utility\GameReviewLookup;
use AutoGamesDiscountCreator\Core\Utility\OfferImageResolver;
use AutoGamesDiscountCreator\Core\Utility\UtilityFactory;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctionsInterface;
use AutoGamesDiscountCreator\Post\DailyRoundupSnapshotRenderer;
/**
 * Class DailyPostStrategy
 *
 * This class is a strategy for creating daily game discount posts.
 */
class DailyPostStrategy implements PostTypeStrategy
{
	/**
	 * @var array $gameData An array of game data to include in the post content.
	 */
	private array $gameData;
	protected WordPressFunctionsInterface $wpFunctions;
	private Date $date;
	private array $marketTarget;
	private array $copySet;
	private OfferImageResolver $offerImageResolver;
	private DailyRoundupSnapshotRenderer $snapshotRenderer;
	private GameReviewLookup $gameReviewLookup;

	/**
	 * DailyPostStrategy constructor.
	 *
	 * @param array $gameData An array of game data to include in the post content.
	 * @param WordPressFunctionsInterface $wpFunctions
	 * @param Date $date
	 */
	public function __construct(array $gameData, WordPressFunctionsInterface $wpFunctions, Date $date, UtilityFactory $utilityFactory, ?array $marketTarget = null)
	{
		$this->wpFunctions = $wpFunctions;
		$this->wpFunctions->setClass($this);
		$this->gameData = $gameData;
		$this->date     = $date;
		$this->offerImageResolver = $utilityFactory->createOfferImageResolver();
		$this->gameReviewLookup = $utilityFactory->createGameReviewLookup();
		$this->marketTarget = $marketTarget ?: (new MarketTargetRepository())->getDefaultTarget();
		$this->copySet = (new MarketTargetRepository())->getCopySet($this->marketTarget);
		$this->snapshotRenderer = new DailyRoundupSnapshotRenderer();
	}

	/**
	 * Returns the post title.
	 *
	 * @return string The post title.
	 */
	public function getPostTitle(): string
	{
		return sprintf($this->copySet['discount_title'], date('d'), $this->getLocalizedMonthName(), date('Y'));
	}

	/**
	 * Returns the game data for the post.
	 *
	 * @param array $where An array of conditions to apply to the game data.
	 *
	 * @return array The game data for the post.
	 */
	public function getGameData(array $where): array
	{
		return $this->gameData;
	}

	/**
	 * Determines whether a post should be created.
	 *
	 * @param array $gameData An array of game data to include in the post content.
	 *
	 * @return bool Whether a post should be created.
	 */
	public function shouldCreatePost(array $gameData): bool
	{
		return $gameData && count($gameData) > 0 && 0 === $this->wpFunctions->postExists($this->getPostTitle());
	}

	/**
	 * Returns the post content.
	 *
	 * @param array $gameData An array of game data to include in the post content.
	 *
	 * @return string The post content.
	 * @throws Exception If the content template file is not found.
	 */
	public function getPostContent(array $gameData): string
	{
		return $this->snapshotRenderer->render($this->buildSnapshotPayload($gameData), $this->copySet);
	}

	public function buildSnapshotPayload(array $gameData): array
	{
		$games = array_map(
			function ($game) {
				$resolvedImageUrl = $this->offerImageResolver->resolve([
					'url' => (string) ($game['url'] ?? ''),
					'store_key' => (string) ($game['store_key'] ?? ''),
					'thumbnail_url' => (string) ($game['thumbnail_url'] ?? ''),
				]);
				$reviewData = $this->gameReviewLookup->lookupBySlug((string) ($game['itad_slug'] ?? ''));

				return [
					'offer_id' => (int) ($game['offer_id'] ?? 0),
					'game_id' => (int) ($game['game_id'] ?? 0),
					'name' => (string) ($game['name'] ?? ''),
					'url' => (string) ($game['url'] ?? ''),
					'price' => (float) ($game['price'] ?? 0),
					'regular_price' => isset($game['regular_price']) ? (float) $game['regular_price'] : null,
					'currency_code' => (string) ($game['currency_code'] ?? 'USD'),
					'cut' => (float) ($game['cut'] ?? 0),
					'store_key' => (string) ($game['store_key'] ?? ''),
					'itad_slug' => (string) ($game['itad_slug'] ?? ''),
					'raw_thumbnail_url' => (string) ($game['thumbnail_url'] ?? ''),
					'resolved_image_url' => $resolvedImageUrl,
					'meta_score' => isset($reviewData['meta_score']) ? (float) $reviewData['meta_score'] : (isset($game['meta_score']) ? (float) $game['meta_score'] : null),
					'user_score' => isset($reviewData['user_score']) ? (float) $reviewData['user_score'] : (isset($game['user_score']) ? (float) $game['user_score'] : null),
					'opencritic_score' => isset($reviewData['opencritic_score']) ? (float) $reviewData['opencritic_score'] : (isset($game['opencritic_score']) ? (float) $game['opencritic_score'] : null),
					'steam_rating' => isset($reviewData['steam_rating']) ? (float) $reviewData['steam_rating'] : (isset($game['steam_rating']) ? (float) $game['steam_rating'] : null),
					'meta_review_count' => isset($reviewData['meta_review_count']) ? (int) $reviewData['meta_review_count'] : null,
					'user_review_count' => isset($reviewData['user_review_count']) ? (int) $reviewData['user_review_count'] : null,
					'opencritic_review_count' => isset($reviewData['opencritic_review_count']) ? (int) $reviewData['opencritic_review_count'] : null,
					'steam_review_count' => isset($reviewData['steam_review_count']) ? (int) $reviewData['steam_review_count'] : null,
				];
			},
			$gameData
		);

		return [
			'version' => 1,
			'generated_at' => current_time('mysql'),
			'market_key' => (string) ($this->marketTarget['market_key'] ?? ''),
			'games' => array_values(array_filter($games, static fn($game): bool => $game['name'] !== '' && $game['url'] !== '')),
		];
	}

	public function renderSnapshotPayload(array $snapshot): string
	{
		return $this->snapshotRenderer->render($snapshot, $this->copySet);
	}

	public function getPostSlug(): string
	{
		$slug = sanitize_title($this->getPostTitle());
		if ($slug !== '') {
			return $slug;
		}

		return sanitize_title(
			sprintf(
				'%s-%s-%s-game-deals',
				$this->marketTarget['seo_path_prefix'] ?? $this->marketTarget['site_section'] ?? 'market',
				date('Y'),
				date('m-d')
			)
		);
	}

	public function getContentKind(): string
	{
		return 'discount_roundup';
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

}

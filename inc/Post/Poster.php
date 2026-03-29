<?php

namespace AutoGamesDiscountCreator\Post;

use AutoGamesDiscountCreator\Core\Settings\SettingsRepository;
use AutoGamesDiscountCreator\Core\Utility\Database;
use AutoGamesDiscountCreator\Core\Utility\UtilityFactory;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use AutoGamesDiscountCreator\Post\Strategy\DailyPostStrategy;
use AutoGamesDiscountCreator\Post\Strategy\PostTypeStrategy;
use Exception;

if (!class_exists('AutoGamesDiscountCreator\Post\Poster')) {
	/**
	 * Class Poster
	 *
	 * This class is responsible for posting game discounts to WordPress.
	 */
	class Poster
	{
		/**
		 * @var array|null $tags An array of tags to include in the post excerpt.
		 */
		private array|null $tags;

		/**
		 * @var Database $database The instance of the Database class.
		 */
		private Database $database;

		/**
		 * @var WordPressFunctions $wpFunctions An instance of the WordPressFunctions class.
		 */
		private WordPressFunctions $wpFunctions;
		private array $settings;

		/**
		 * @var PostTypeStrategy $postTypeStrategy The strategy for the post type (daily or free games).
		 */
		private PostTypeStrategy $postTypeStrategy;

		/**
		 * @var int POST_AUTHOR The author ID for the post.
		 */
		public const POST_AUTHOR = 1;

		/**
		 * @var int POST_CATEGORY The category ID for the post.
		 */
		public const POST_CATEGORY = 14;

		/**
		 * @var string TAGS A comma-separated string of tags to include in the post excerpt.
		 */
		public const TAGS = '#indirim, #discount, #gamedeals, #steam, #steamindirim, #indirimlioyun, #ucuzoyun, #freegame, #ucretsiz, #ucretsizoyun, #free';

		/**
		 * Poster constructor.
		 *
		 * @param PostTypeStrategy $postTypeStrategy The strategy for the post type (daily or free games).
		 *
		 * @throws Exception
		 */
		public function __construct(PostTypeStrategy $postTypeStrategy)
		{
			if (!function_exists('post_exists')) {
				include_once ABSPATH . 'wp-admin/includes/post.php';
			}

			$this->database         = Database::getInstance();
			$this->wpFunctions      = new WordPressFunctions($this);
			$this->postTypeStrategy = $postTypeStrategy;
			$this->settings         = (new SettingsRepository())->getAll();
		}

		/**
		 * Posts game discounts to WordPress.
		 *
		 * This method retrieves the game data, checks if a post should be created, and creates a new post
		 * with the game data if necessary.
		 */
		public function post()
		{
			if (!empty($this->settings['general']['dry_run'])) {
				return;
			}

			$game_data  = $this->postTypeStrategy->getGameData(['price[>]' => 0]);
			$post_title = $this->postTypeStrategy->getPostTitle();
			$posting_settings = $this->getPostingSettings();
			$post_tags  = (string) ($posting_settings['tags'] ?? self::TAGS);
			$post_author = (int) ($posting_settings['author_id'] ?? self::POST_AUTHOR);
			$post_category = (int) ($posting_settings['category_id'] ?? self::POST_CATEGORY);
			$post_status = (string) ($posting_settings['post_status'] ?? 'draft');
			if (!in_array($post_status, ['draft', 'publish'], true)) {
				$post_status = 'draft';
			}

			$existing_post_id = (int) ($this->wpFunctions->postExists($post_title) ?: 0);
			if (!$this->postTypeStrategy->shouldCreatePost($game_data) && !($this->postTypeStrategy instanceof DailyPostStrategy && $existing_post_id > 0)) {
				return;
			}

			$market_target = $this->postTypeStrategy->getMarketTarget();
			$post_slug = $this->postTypeStrategy->getPostSlug();
			$post_type = $this->postTypeStrategy->getContentKind() === 'discount_roundup' ? 'agdc_roundup' : 'post';
			$snapshot_payload = null;
			$post_content = $this->postTypeStrategy->getPostContent($game_data);
			if ($this->postTypeStrategy instanceof DailyPostStrategy) {
				$snapshot_payload = $this->postTypeStrategy->buildSnapshotPayload($game_data);
				if ($existing_post_id > 0) {
					$snapshot_payload = $this->mergeDailySnapshotPayload($existing_post_id, $snapshot_payload);
				}
				$post_content = '';
			}
			$post_args = [
				'post_type'     => $post_type,
				'post_content'  => $post_content,
				'post_status'   => $post_status,
				'post_author'   => $post_author,
				'post_excerpt'  => $post_title . ' ' . $post_tags,
				'post_name'     => $post_slug,
				'post_title'    => $post_title,
				'tags_input'    => $post_tags,
			];
			if ($post_category > 0) {
				$post_args['post_category'] = [$post_category];
			}

			if ($this->postTypeStrategy instanceof DailyPostStrategy && $existing_post_id > 0) {
				$post_args['ID'] = $existing_post_id;
				$post_id = wp_update_post($post_args, true);
			} else {
				$post_id = $this->wpFunctions->wpInsertPost($post_args);
			}

			if ($post_id && !is_wp_error($post_id)) {
				$this->updatePostMeta((int) $post_id, $post_title, $post_tags);
				update_post_meta($post_id, '_agdc_market_key', (string) ($market_target['market_key'] ?? ''));
				update_post_meta($post_id, '_agdc_language_code', (string) ($market_target['language_code'] ?? ''));
				update_post_meta($post_id, '_agdc_site_section', (string) ($market_target['site_section'] ?? ''));
				update_post_meta($post_id, '_agdc_content_kind', $this->postTypeStrategy->getContentKind());
				if ($this->postTypeStrategy instanceof DailyPostStrategy && is_array($snapshot_payload)) {
					update_post_meta($post_id, '_agdc_snapshot_payload', $snapshot_payload);
				}
				$this->markGamesAsPosted($game_data, (int) $post_id, $post_status);
				if ($this->postTypeStrategy instanceof DailyPostStrategy && is_array($snapshot_payload)) {
					$this->refreshDailySnapshotStorage((int) $post_id, $snapshot_payload);
				}
			}
		}

		/**
		 * Updates the post meta.
		 *
		 * @param int $postId The ID of the post to update the meta for.
		 * @param string $postTitle The title of the post to update the meta for.
		 *
		 * @throws Exception if the post meta could not be updated.
		 */
		private function updatePostMeta(int $postId, string $postTitle, string $postTags): void
		{
			$message = $postTitle . ' ' . $postTags;
			update_post_meta($postId, '_wpas_mess', $message);
		}

		private function getPostingSettings(): array
		{
			$base = is_array($this->settings['posting'] ?? null) ? $this->settings['posting'] : [];
			$kind = $this->postTypeStrategy->getContentKind();
			$override = [];

			if ($kind === 'free_game') {
				$override = is_array($this->settings['posting_free'] ?? null) ? $this->settings['posting_free'] : [];
			} elseif ($kind === 'discount_roundup') {
				$override = is_array($this->settings['posting_daily'] ?? null) ? $this->settings['posting_daily'] : [];
			}

			return array_merge($base, $override);
		}

		/**
		 * Marks the games as posted in the database.
		 *
		 * @param array $gameData An array of game data to mark as posted.
		 *
		 * @throws Exception if the games could not be marked as posted in the database.
		 */
		private function markGamesAsPosted(array $gameData, int $postId, string $postStatus): void
		{
			global $wpdb;

			$generated_posts_table = $wpdb->prefix . 'agdc_generated_posts';
			$market_target = $this->postTypeStrategy->getMarketTarget();
			foreach ($gameData as $game) {
				try {
					$offer_id = (int) ($game['offer_id'] ?? $game['price_id'] ?? 0);
					if ($offer_id > 0) {
						$wpdb->replace(
							$generated_posts_table,
							[
								'wp_post_id' => $postId,
								'game_id' => (int) ($game['game_id'] ?? 0) ?: null,
								'offer_id' => $offer_id,
								'market_target_id' => isset($market_target['id']) ? (int) $market_target['id'] : null,
								'content_kind' => $this->postTypeStrategy->getContentKind(),
								'language_code' => (string) ($market_target['language_code'] ?? ($game['language_code'] ?? '')),
								'post_status' => $postStatus,
								'published_at' => $postStatus === 'publish' ? current_time('mysql') : null,
								'created_at' => current_time('mysql'),
								'updated_at' => current_time('mysql'),
							]
						);
						continue;
					}

					$this->database->update(
						'rambouillet_posts',
						['status_wordpress' => 1],
						['price_id' => (int)$game['price_id']]
					);
				} catch (Exception $exception) {
					throw $exception;
				}
			}
		}

		private function mergeDailySnapshotPayload(int $postId, array $newSnapshot): array
		{
			$merged = [];
			$snapshots = [
				get_post_meta($postId, '_agdc_snapshot_payload', true),
				$this->buildSnapshotFromGeneratedPosts($postId),
				$newSnapshot,
			];

			foreach ($snapshots as $snapshot) {
				if (!is_array($snapshot) || !is_array($snapshot['games'] ?? null)) {
					continue;
				}

				foreach ($snapshot['games'] as $game) {
					if (!is_array($game)) {
						continue;
					}

					$key = (string) ($game['offer_id'] ?? '');
					if ($key === '' || $key === '0') {
						$key = md5((string) ($game['name'] ?? '') . '|' . (string) ($game['url'] ?? ''));
					}

					$merged[$key] = array_merge($merged[$key] ?? [], $game);
				}
			}

			$newSnapshot['games'] = array_values($merged);

			return $newSnapshot;
		}

		private function buildSnapshotFromGeneratedPosts(int $postId): array
		{
			global $wpdb;

			$generatedPosts = $wpdb->prefix . 'agdc_generated_posts';
			$offers = $wpdb->prefix . 'agdc_offers';
			$games = $wpdb->prefix . 'agdc_games';
			$stores = $wpdb->prefix . 'agdc_stores';

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT gp.offer_id, gp.game_id, g.canonical_name AS game_name, g.artwork_url, o.deeplink_url, o.sale_price_amount, o.regular_price_amount, o.currency_code, o.discount_percent, s.store_key
					FROM {$generatedPosts} gp
					LEFT JOIN {$offers} o ON o.id = gp.offer_id
					LEFT JOIN {$games} g ON g.id = gp.game_id
					LEFT JOIN {$stores} s ON s.id = o.store_id
					WHERE gp.wp_post_id = %d AND gp.content_kind = %s
					ORDER BY gp.id ASC",
					$postId,
					'discount_roundup'
				),
				ARRAY_A
			);

			if (!is_array($rows) || $rows === []) {
				return ['games' => []];
			}

			$gamesPayload = [];
			foreach ($rows as $row) {
				$gamesPayload[] = [
					'offer_id' => (int) ($row['offer_id'] ?? 0),
					'game_id' => (int) ($row['game_id'] ?? 0),
					'name' => (string) ($row['game_name'] ?? ''),
					'url' => (string) ($row['deeplink_url'] ?? ''),
					'price' => isset($row['sale_price_amount']) ? (float) $row['sale_price_amount'] : 0.0,
					'regular_price' => isset($row['regular_price_amount']) ? (float) $row['regular_price_amount'] : null,
					'currency_code' => (string) ($row['currency_code'] ?? 'USD'),
					'cut' => isset($row['discount_percent']) ? (float) $row['discount_percent'] : 0.0,
					'store_key' => (string) ($row['store_key'] ?? ''),
					'raw_thumbnail_url' => (string) ($row['artwork_url'] ?? ''),
					'meta_score' => null,
					'user_score' => null,
					'opencritic_score' => null,
					'steam_rating' => null,
				];
			}

			return $this->enrichSnapshotPayload(['games' => $gamesPayload]);
		}

		private function refreshDailySnapshotStorage(int $postId, array $baseSnapshot): void
		{
			if (!$this->postTypeStrategy instanceof DailyPostStrategy) {
				return;
			}

			$finalSnapshot = $this->enrichSnapshotPayload(
				$this->mergeDailySnapshotPayload($postId, $baseSnapshot)
			);
			update_post_meta($postId, '_agdc_snapshot_payload', $finalSnapshot);
		}

		private function enrichSnapshotPayload(array $snapshot): array
		{
			$games = $snapshot['games'] ?? null;
			if (!is_array($games) || $games === []) {
				return $snapshot;
			}

			$resolver = (new UtilityFactory())->createOfferImageResolver();

			foreach ($games as $index => $game) {
				if (!is_array($game)) {
					continue;
				}

				if (!empty($game['resolved_image_url'])) {
					continue;
				}

				$imageUrl = $resolver->resolve([
					'url' => (string) ($game['url'] ?? ''),
					'store_key' => (string) ($game['store_key'] ?? ''),
					'thumbnail_url' => (string) ($game['raw_thumbnail_url'] ?? ''),
				]);

				if (!is_string($imageUrl) || $imageUrl === '') {
					continue;
				}

				$games[$index]['resolved_image_url'] = $imageUrl;
			}

			$snapshot['games'] = $games;

			return $snapshot;
		}
	}
}

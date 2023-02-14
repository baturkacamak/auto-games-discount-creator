<?php

namespace AutoGamesDiscountCreator\Post;

use AutoGamesDiscountCreator\Core\Utility\Database;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
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
		 */
		public function __construct(PostTypeStrategy $postTypeStrategy)
		{
			if (!function_exists('post_exists')) {
				include_once ABSPATH . 'wp-admin/includes/post.php';
			}

			$this->database         = Database::getInstance();
			$this->wpFunctions      = new WordPressFunctions($this);
			$this->postTypeStrategy = $postTypeStrategy;
		}

		/**
		 * Posts game discounts to WordPress.
		 *
		 * This method retrieves the game data, checks if a post should be created, and creates a new post
		 * with the game data if necessary.
		 */
		public function post()
		{
			$gameData  = $this->postTypeStrategy->getGameData(['price[>]' => 0]);
			$postTitle = $this->postTypeStrategy->getPostTitle();

			if ($this->postTypeStrategy->shouldCreatePost($gameData)) {
				$postId = $this->wpFunctions->wpInsertPost([
					'post_content'  => $this->postTypeStrategy->getPostContent($gameData),
					'post_status'   => 'publish',
					'post_author'   => self::POST_AUTHOR,
					'post_excerpt'  => $postTitle . ' ' . self::TAGS,
					'post_title'    => $postTitle,
					'tags_input'    => self::TAGS,
					'post_category' => [self::POST_CATEGORY],
				]);

				if ($postId) {
					$this->updatePostMeta($postId, $postTitle);
					$this->markGamesAsPosted($gameData);
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
		private function updatePostMeta(int $postId, string $postTitle): void
		{
			$message = $postTitle . ' ' . self::TAGS;
			update_post_meta($postId, '_wpas_mess', $message);
		}

		/**
		 * Marks the games as posted in the database.
		 *
		 * @param array $gameData An array of game data to mark as posted.
		 *
		 * @throws Exception if the games could not be marked as posted in the database.
		 */
		private function markGamesAsPosted(array $gameData): void
		{
			foreach ($gameData as $game) {
				try {
					$this->database->update(
						'rambouillet_posts',
						['status_wordpress' => 1],
						['price_id' => $game->price_id]
					);
				} catch (Exception $exception) {
					throw $exception;
				}
			}
		}
	}
}

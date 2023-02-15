<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 14/2/23
 * Time: 18:28
 */

namespace AutoGamesDiscountCreator\Post\Strategy;

use AutoGamesDiscountCreator\Core\Utility\Date;
use Philo\Blade\Blade;

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

	/**
	 * DailyPostStrategy constructor.
	 *
	 * @param array $gameData An array of game data to include in the post content.
	 */
	public function __construct(array $gameData)
	{
		$this->gameData = $gameData;
	}

	/**
	 * Returns the post title.
	 *
	 * @return string The post title.
	 */
	public function getPostTitle(): string
	{
		return sprintf(
			"%d %s %d Steam İndirimleri",
			date('d'),
			(new Date())->getTurkishName(),
			date('Y')
		);
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
		return $gameData && count($gameData) > 0 && 0 === post_exists($this->getPostTitle());
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
		$gameData = array_map(
			function ($game) {
				$exploded = explode('/', $game['url']);
				$steam_id = $exploded[count($exploded) - 1];
				$type     = $exploded[3];

				return [
					'name'          => $game['name'],
					'thumbnail_url' => "https://steamcdn-a.akamaihd.net/steam/{$type}s/{$steam_id}/header.jpg",
					'price'         => $game['price'],
					'cut'           => $game['cut'],
					'url'           => $game['url'],
				];
			},
			$gameData
		);

		return $this->getContent('content-tr', ['games' => $gameData]);
	}

	/**
	 * Returns the content for the specified template and data.
	 *
	 * @param string $template The name of the template file.
	 * @param array $data An array of data to use in the template.
	 *
	 * @return string The content for the specified template and data.
	 * @throws Exception If the template file is not found.
	 */
	private function getContent(string $template, array $data): string
	{
		$views = __DIR__ . '/../content';
		$cache = __DIR__ . '/../content/cache';
		$blade = new Blade($views, $cache);

		return $blade->view()->make($template, $data)->render();
	}
}

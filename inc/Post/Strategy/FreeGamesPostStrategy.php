<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 14/2/23
 * Time: 18:29
 */

namespace AutoGamesDiscountCreator\Post\Strategy;

use AutoGamesDiscountCreator\Core\Utility\Date;
use AutoGamesDiscountCreator\Core\Utility\ImageRetriever;
use AutoGamesDiscountCreator\Core\Utility\UtilityFactory;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctionsInterface;
use Philo\Blade\Blade;

class FreeGamesPostStrategy implements PostTypeStrategy
{
	/**
	 * @var array $gameData An array of game data for the free game.
	 */
	private array $gameData;
	private ImageRetriever $imageRetriever;
	private WordPressFunctionsInterface $wordpressFunctions;

	/**
	 * FreeGamesPostStrategy constructor.
	 *
	 * @param array $gameData An array of game data for the free game.
	 * @param UtilityFactory $utilityFactory The utility factory to use for creating the ImageRetriever instance.
	 * @param WordPressFunctionsInterface $wordpressFunctions The WordPress functions instance.
	 */
	public function __construct(array $gameData, UtilityFactory $utilityFactory, WordPressFunctionsInterface $wordpressFunctions)
	{
		$this->gameData = $gameData;

		$this->imageRetriever = $utilityFactory->createImageRetriever();
		$this->wordpressFunctions = $wordpressFunctions;
	}

	/**
	 * Returns the post title for the free game.
	 *
	 * @return string The post title for the free game.
	 */
	public function getPostTitle(): string
	{
		return sprintf(
			'Ucretsiz Oyun // %s // %d %s %d',
			$this->gameData['name'],
			date('d'),
			(new Date())->getTurkishName(),
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
		$this->gameData['thumbnail_url'] = $this->imageRetriever->retrieve($gameData[0]['url']) ?? false;

		return $this->getContent('content-free-tr', ['game' => $this->gameData]);
	}

	/**
	 * Returns the content for a template file.
	 *
	 * @param string $template The name of the template file.
	 * @param array $data An array of data to use in the template.
	 *
	 * @return string The content for the template file.
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

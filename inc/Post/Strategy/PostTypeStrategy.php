<?php

/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 14/2/23
 * Time: 18:27
 */

namespace AutoGamesDiscountCreator\Post\Strategy;

/**
 * Interface PostTypeStrategy
 *
 * This interface defines the methods that the post type strategies must implement.
 */
interface PostTypeStrategy
{
	/**
	 * Gets the post title for the current post type.
	 *
	 * @return string The post title.
	 */
	public function getPostTitle(): string;

	/**
	 * Gets the game data for the current post type.
	 *
	 * @param array $where The conditions to use when retrieving the data.
	 *
	 * @return array The game data.
	 */
	public function getGameData(array $where): array;

	/**
	 * Checks whether a new post should be created for the current post type.
	 *
	 * @param array $gameData The game data to use when checking whether a new post should be created.
	 *
	 * @return bool True if a new post should be created, false otherwise.
	 */
	public function shouldCreatePost(array $gameData): bool;

	/**
	 * Gets the post content for the current post type.
	 *
	 * @param array $gameData The game data to use when creating the post content.
	 *
	 * @return string The post content.
	 */
	public function getPostContent(array $gameData): string;
}

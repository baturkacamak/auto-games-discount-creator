<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 17/2/23
 * Time: 22:29
 */

use AutoGamesDiscountCreator\Core\Utility\Date;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctionsInterface;
use AutoGamesDiscountCreator\Post\Strategy\DailyPostStrategy;
use PHPUnit\Framework\TestCase;

class DailyPostStrategyTest extends TestCase
{
	public function testShouldCreatePostReturnsTrueWhenGameDataNotEmptyAndPostDoesNotExist()
	{
		// Arrange
		$gameData          = [['name' => 'Test Game']];
		$wpFunctionsMock   = $this->createMock(WordPressFunctions::class);
		$dateMock          = $this->createMock(Date::class);
		$dailyPostStrategy = new DailyPostStrategy($gameData, $wpFunctionsMock, $dateMock);
		$postTitle         = date('d') . '  ' . date('Y') . ' Steam İndirimleri';
		$wpFunctionsMock->expects($this->once())
		                ->method('postExists')
		                ->with($postTitle)
		                ->willReturn(0);

		// Act
		$shouldCreatePost = $dailyPostStrategy->shouldCreatePost($gameData);

		// Assert
		$this->assertTrue($shouldCreatePost);
	}

	public function testShouldCreatePostReturnsFalseWhenGameDataIsEmpty()
	{
		// Arrange
		$gameData          = [];
		$wpFunctionsMock   = $this->createMock(WordPressFunctions::class);
		$dateMock          = $this->createMock(Date::class);
		$dailyPostStrategy = new DailyPostStrategy($gameData, $wpFunctionsMock, $dateMock);

		// Act
		$shouldCreatePost = $dailyPostStrategy->shouldCreatePost($gameData);

		// Assert
		$this->assertFalse($shouldCreatePost);
	}

	public function testShouldCreatePostReturnsFalseWhenPostExists()
	{
		// Arrange
		$gameData          = [['name' => 'Test Game']];
		$wpFunctionsMock   = $this->createMock(WordPressFunctions::class);
		$dateMock          = $this->createMock(Date::class);
		$dailyPostStrategy = new DailyPostStrategy($gameData, $wpFunctionsMock, $dateMock);
		$postTitle         = date('d') . '  ' . date('Y') . ' Steam İndirimleri';
		$wpFunctionsMock->expects($this->once())
		                ->method('postExists')
		                ->with($postTitle)
		                ->willReturn(1);

		// Act
		$shouldCreatePost = $dailyPostStrategy->shouldCreatePost($gameData);

		// Assert
		$this->assertFalse($shouldCreatePost);
	}

	public function testGetGameDataReturnsGameData()
	{
		// Arrange
		$gameData      = [['name' => 'Test Game']];
		$mockFunctions = $this->createMock(WordPressFunctionsInterface::class);
		$dateMock      = $this->createMock(Date::class);

		$dailyPostStrategy = new DailyPostStrategy($gameData, $mockFunctions, $dateMock);

		// Act
		$returnedGameData = $dailyPostStrategy->getGameData([]);

		// Assert
		$this->assertEquals($gameData, $returnedGameData);
	}

	public function testGetPostContentReturnsString()
	{
		// Arrange
		$gameData = [
			[
				'name'  => 'Test Game',
				'url'   => 'https://store.steampowered.com/app/123456',
				'price' => '$9.99',
				'cut'   => '50%',
			],
		];

		// Create a mock WordPressFunctionsInterface instance
		$wpFunctionsMock = $this->createMock(WordPressFunctionsInterface::class);
		$wpFunctionsMock->method('postExists')->willReturn(false);
		$dateMock = $this->createMock(Date::class);

		$dailyPostStrategy = new DailyPostStrategy($gameData, $wpFunctionsMock, $dateMock);

		// Act
		$postContent = $dailyPostStrategy->getPostContent([]);

		// Assert
		$this->assertIsString($postContent);
		$this->assertEmpty($postContent);
	}
}

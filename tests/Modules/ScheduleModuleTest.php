<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 18/2/23
 * Time: 20:12
 */

use AutoGamesDiscountCreator\Core\Utility\UtilityFactory;
use AutoGamesDiscountCreator\Core\WordPress\WordPressFunctions;
use AutoGamesDiscountCreator\Modules\ScheduleModule;
use AutoGamesDiscountCreator\Post\Poster;
use AutoGamesDiscountCreator\Post\Strategy\FreeGamesPostStrategy;
use PHPUnit\Framework\TestCase;

class ScheduleModuleTest extends TestCase
{
	public function testStartHourlyPostTaskPostsCorrectNumberOfPostsWhenGameDataExists()
	{
		// Arrange
		$gameInformation = [
			[
				'id'        => 1,
				'name'      => 'Test Game 1',
				'image'     => 'https://example.com/test-game-1.jpg',
				'price'     => 10.0,
				'discount'  => 50,
				'platforms' => ['PC'],
				'link'      => 'https://example.com/test-game-1',
			],
			[
				'id'        => 2,
				'name'      => 'Test Game 2',
				'image'     => 'https://example.com/test-game-2.jpg',
				'price'     => 20.0,
				'discount'  => 25,
				'platforms' => ['PS4', 'Xbox One'],
				'link'      => 'https://example.com/test-game-2',
			],
		];

		$utilityFactoryMock     = $this->createMock(UtilityFactory::class);
		$wordpressFunctionsMock = $this->getMockBuilder(WordPressFunctions::class)
		                               ->disableOriginalConstructor()
		                               ->onlyMethods(['scheduleEvent', 'addHook'])
		                               ->getMock();

		$wordpressFunctionsMock->expects($this->atLeastOnce())
		                       ->method('addHook');

		$freeGamesPostStrategyMock = $this->createMock(FreeGamesPostStrategy::class);

		$posterMock = $this->createMock(Poster::class);
		$posterMock->expects($this->exactly(count($gameInformation)))
		           ->method('post')
		           ->with($freeGamesPostStrategyMock);

		$scheduleModule = new ScheduleModule($wordpressFunctionsMock);
		$scheduleModule->setup();

		// Inject the mock WordPressFunctions object
		$scheduleModule->startHourlyPostTask(
			$gameInformation,
			$utilityFactoryMock,
			$wordpressFunctionsMock,
		);
	}
}

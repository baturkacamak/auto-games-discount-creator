<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 18/2/23
 * Time: 17:47
 */

namespace AutoGamesDiscountCreator\Core\Utility;

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
		return new ImageRetriever(new WebClient(), new DOMHandler());
	}
}

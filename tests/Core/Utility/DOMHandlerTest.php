<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 17/2/23
 * Time: 18:13
 */

namespace AutoGamesDiscountCreator\Core\Utility;

use PHPUnit\Framework\TestCase;

class DOMHandlerTest extends TestCase
{
	public function testGetXpathNodeValueReturnsNullWhenNoResults()
	{
		$domHandler = new DOMHandler();
		$xpath      = new \DOMXPath(new \DOMDocument());
		$result     = $domHandler->getXpathNodeValue($xpath->query('//non-existent-tag'));
		$this->assertNull($result);
	}

	public function testGetFirstChildByTagNameReturnsChildNode()
	{
		$domHandler = new DOMHandler();
		$htmlString = '<div><p>test</p></div>';
		$domXPath   = $domHandler->createXpathFromHtml($htmlString);
		$parentNode = $domXPath->query('//div')->item(0);
		$childNode  = $domHandler->getFirstChildByTagName($parentNode, 'p');
		$this->assertEquals('test', $childNode->getTextContent());
	}

	public function testGetAllChildrenByClassReturnsArrayOfChildNodes()
	{
		$domHandler = new DOMHandler();
		$htmlString = '<ul><li class="item">test 1</li><li class="item">test 2</li></ul>';
		$domXPath   = $domHandler->createXpathFromHtml($htmlString);
		$parentNode = $domXPath->query('//ul')->item(0);
		$childNodes = $domHandler->getAllChildrenByClass($parentNode, 'item');
		$this->assertEquals(2, count($childNodes));
		$this->assertEquals('test 1', $childNodes[0]->getTextContent());
		$this->assertEquals('test 2', $childNodes[1]->getTextContent());
	}
}

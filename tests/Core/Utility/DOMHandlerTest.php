<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 17/2/23
 * Time: 18:13
 */

namespace AutoGamesDiscountCreator\Core\Utility;

use DOMNode;
use DOMNodeList;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class DOMHandlerTest extends TestCase
{
	protected DOMHandler $domHandler;
	protected string $htmlString;

	protected function setUp(): void
	{
		parent::setUp();

		$this->htmlString = '
		<div>
		  <h1>Page Title</h1>
		  <p>Some introductory text.</p>
		  <ul>
		    <li>Item 1</li>
		    <li>Item 2</li>
		    <li>Item 3</li>
		  </ul>
		  <table>
		    <thead>
		      <tr>
		        <th>Column 1</th>
		        <th>Column 2</th>
		        <th>Column 3</th>
		      </tr>
		    </thead>
		    <tbody>
		      <tr>
		        <td>Row 1, Column 1</td>
		        <td>Row 1, Column 2</td>
		        <td>Row 1, Column 3</td>
		      </tr>
		      <tr>
		        <td>Row 2, Column 1</td>
		        <td>Row 2, Column 2</td>
		        <td>Row 2, Column 3</td>
		      </tr>
		      <tr>
		        <td>Row 3, Column 1</td>
		        <td>Row 3, Column 2</td>
		        <td>Row 3, Column 3</td>
		      </tr>
		    </tbody>
		  </table>
		  <form>
		    <input type="text" name="name" placeholder="Name">
		    <input type="email" name="email" placeholder="Email">
		    <textarea name="message" placeholder="Message"></textarea>
		    <button type="submit">Submit</button>
		  </form>
		</div>
		';
		$this->domHandler = new DOMHandler();
	}

	public function testGetXpathNodeValueReturnsNullWhenNoResults()
	{
		$xpath  = new \DOMXPath(new \DOMDocument());
		$result = $this->domHandler->getFirstNodeValue($xpath->query('//non-existent-tag'));
		$this->assertNull($result);
	}

	public function testGetFirstChildByTagNameReturnsChildNode()
	{
		$htmlString = '<div><p>test</p></div>';
		$domXPath   = $this->domHandler->createXpathFromHtml($htmlString);
		$parentNode = $domXPath->query('//div')->item(0);
		$childNode  = $this->domHandler->getFirstChildByTagName($parentNode, 'p');
		$this->assertEquals('test', $childNode->getTextContent($parentNode));
	}

	public function testGetAllChildrenByClassReturnsArrayOfChildNodes()
	{
		$htmlString = '<ul><li class="item">test 1</li><li class="item">test 2</li></ul>';
		$domXPath   = $this->domHandler->createXpathFromHtml($htmlString);
		$parentNode = $domXPath->query('//ul')->item(0);
		$childNodes = $domXPath->query('li', $parentNode);
		$this->assertEquals(2, count($childNodes));
		$this->assertEquals('test 1', $this->domHandler->getTextContent($childNodes->item(0)));
		$this->assertEquals('test 2', $this->domHandler->getTextContent($childNodes->item(1)));
	}

	public function testGetFirstNodeValueReturnsNodeValueWhenNodesFound()
	{
		// Arrange
		$domXPath = $this->domHandler->createXpathFromHtml($this->htmlString);
		$nodeList = $domXPath->query('//p');

		// Act
		$result = $this->domHandler->getFirstNodeValue($nodeList);

		// Assert
		$this->assertEquals('Some introductory text.', $result);
	}

	public function testGetFirstNodeValueReturnsFalseWhenNoNodesFound()
	{
		// Arrange
		$domXPath = $this->domHandler->createXpathFromHtml($this->htmlString);
		$nodeList = $domXPath->query('//span');

		// Act
		$result = $this->domHandler->getFirstNodeValue($nodeList);

		// Assert
		$this->assertNull($result);
	}

	public function testGetDomNodeValueReturnsNodeValue(): void
	{
		$domDocument = $this->domHandler->createXpathFromHtml($this->htmlString);
		$nodeList    = $domDocument->query('//h1');

		$nodeValue = $this->domHandler->getDomNodeValue($nodeList->item(0));

		$this->assertEquals('Page Title', $nodeValue);
	}

	public function testGetNodeAttributeReturnsCorrectAttribute()
	{
		// Arrange
		$htmlString = '<div class="test-class"><p>test</p></div>';
		$domXPath   = $this->domHandler->createXpathFromHtml($htmlString);
		$node       = $domXPath->query('//div')->item(0);

		// Act
		$result = $this->domHandler->getNodeAttribute($node, 'class');

		// Assert
		$this->assertEquals('test-class', $result);
	}

	public function testGetNodeAttributeReturnsEmptyStringWhenAttributeNotPresent()
	{
		// Arrange
		$htmlString = '<div><p>test</p></div>';
		$domXPath   = $this->domHandler->createXpathFromHtml($htmlString);
		$node       = $domXPath->query('//div')->item(0);

		// Act
		$result = $this->domHandler->getNodeAttribute($node, 'class');

		// Assert
		$this->assertEquals('', $result);
	}

}

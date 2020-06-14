<?php

namespace Rambouillet\Utility;

use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{

    public function testGetXpath()
    {
        $sampleHtml = '<!doctype html><html lang="en"><head><meta charset="UTF-8">' .
            '<meta name="viewport" ' .
            'content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">' .
            '<meta http-equiv="X-UA-Compatible" content="ie=edge">' .
            '<title>Document</title></head><body><h1>Hello World!</h1></body></html>';

        $dom = Helper::getXpath($sampleHtml);
        $this->assertInstanceOf(\DOMXPath::class, $dom);
        $this->assertEquals('1', $dom->query('//h1')->length);
    }

    public function testGetTurkishDate()
    {
        $date = '2014-06-09';
        $currentTurkishMonthName = Helper::getTurkishDateName($date);
        $currentTurkishMonthNameShort = Helper::getTurkishDateName($date, 'M');
        $currentTurkishDayNameShort = Helper::getTurkishDateName($date, 'D');
        $currentTurkishDayName = Helper::getTurkishDateName($date, 'l');

        $this->assertEquals('Haziran', $currentTurkishMonthName);
        $this->assertEquals('Haz', $currentTurkishMonthNameShort);
        $this->assertEquals('Pazartesi', $currentTurkishDayName);
        $this->assertEquals('Pts', $currentTurkishDayNameShort);
    }

    public function testGetRemoteImage()
    {
        $imageUrl = Helper::getRemoteImage('https://www.epicgames.com/store/en-US/product/pathway/home');

        $this->assertEquals(
            'https://cdn2.unrealengine.com/egs-pathway-robotality-s6-1200x1600-172767078.jpg',
            $imageUrl
        );
    }
}

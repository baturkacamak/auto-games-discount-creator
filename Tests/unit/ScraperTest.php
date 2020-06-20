<?php

use GuzzleHttp\Client as GuzzleClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Rambouillet\Scraper;

class ScraperTest extends TestCase
{

    public function testCanBeInstantiated()
    {
        $scraper = new Scraper();
        $this->assertInstanceOf(Scraper::class, $scraper);
    }

    public function testGetQueryResult()
    {
        $mockGuzzle = $this->mockGuzzleRequest('test', 'test.php', 'html');
        $scraper = new Scraper();
        $scraper->setGuzzle($mockGuzzle);

        $filter = '-type/7,-type/8,-type/6,&storelow,&cut/75/100,&metauser/75/100,&steam,&steamcnt/500/max,-dlc';

        $result  = $scraper->getQueryResult($filter);
        $this->assertEquals('html', $result);
    }

    public function it_can_retrieve_metadata()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.boxapi.com/2/files/get_metadata',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(['name' => 'math'], $client->getMetadata('Homework/math'));
    }

    private function mockGuzzleRequest($expectedResponse, $expectedEndpoint, $expectedParams)
    {
        $mock_response = $this->getMockBuilder(ResponseInterface::class)->getMock();

        if ($expectedResponse) {
            $mock_response->expects($this->once())
                ->method('getBody')
                ->willReturn($expectedResponse);
        }

        $mock_guzzle = $this->getMockBuilder(GuzzleClient::class)
            ->setMethods(['get'])
            ->getMock();

        $mock_guzzle->expects($this->once())
            ->method('get')
            ->with($expectedEndpoint, $expectedParams)
            ->willReturn($mock_response);

        return $mock_guzzle;
    }
}

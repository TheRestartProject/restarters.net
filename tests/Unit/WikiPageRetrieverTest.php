<?php

namespace Tests\Unit;

use App\Helpers\CachingWikiPageRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WikiPageRetrieverTest extends TestCase
{
    public function testOneResult()
    {
        $apiEndpointBase = env('WIKI_URL').'/api.php';
        $wikiPageRetriever = new CachingWikiPageRetriever($apiEndpointBase);

        $result = $wikiPageRetriever->getRandomWikiPages(1);

        $this->assertCount(1, $result);
    }

    public function testFiveResults()
    {
        $apiEndpointBase = env('WIKI_URL').'/api.php';
        $wikiPageRetriever = new CachingWikiPageRetriever($apiEndpointBase);

        $result = $wikiPageRetriever->getRandomWikiPages(5);

        $this->assertCount(5, $result);
    }

    /*public function testCaching()
    {
        Cache::shouldReceive('has')
            ->twice()
            ->with('wiki_pages');
        Cache::shouldReceive('put')->once();
        Cache::shouldReceive('get')
            ->once()
            ->with('wiki_pages');

        $apiEndpointBase = env('WIKI_URL') . '/api.php';
        $wikiPageRetriever = new CachingWikiPageRetriever($apiEndpointBase);

        $result = $wikiPageRetriever->getRandomWikiPages(5);

        $result = $wikiPageRetriever->getRandomWikiPages(5);
    }*/
}

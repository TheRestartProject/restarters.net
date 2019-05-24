<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use App\Helpers\CachingWikiPageRetriever;

class WikiPageRetrieverTest extends TestCase
{
    // Not sure what this test is supposed to be doing exactly?
    // TODO: mock the CachingWikiPageRetriever
    public function testOneResult()
    {
        $apiEndpointBase = env('WIKI_URL') . '/api.php';
        $wikiPageRetriever = new CachingWikiPageRetriever($apiEndpointBase);
        $result = $wikiPageRetriever->getRandomWikiPages(1);

        $this->assertCount(1, $result);
        // this is a hack to make sure the following test
        // gets the correct number of results
        $wikiPageRetriever->flushPages();

    }

    public function testFiveResults()
    {
        $apiEndpointBase = env('WIKI_URL') . '/api.php';
        $wikiPageRetriever = new CachingWikiPageRetriever($apiEndpointBase);
        $result = $wikiPageRetriever->getRandomWikiPages(5);

        $this->assertCount(5, $result);
        $wikiPageRetriever->flushPages();
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

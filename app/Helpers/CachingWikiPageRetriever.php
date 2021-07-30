<?php

namespace App\Helpers;

use Cache;

class CachingWikiPageRetriever
{
    protected $cacheKey;
    protected $apiEndpointBase;

    public function __construct($apiEndpointBase)
    {
        $this->cacheKey = 'wiki_pages';
        $this->apiEndpointBase = $apiEndpointBase;
    }

    public function getRandomWikiPages($numPages = 5)
    {
        if (Cache::has($this->cacheKey)) {
            return Cache::get($this->cacheKey);
        }

        $endpoint = $this->apiEndpointBase.'?action=query&rnnamespace=0&list=random&rnlimit='.$numPages.'&format=json';

        $pages_json = [];
        try {
            $raw_json = file_get_contents($endpoint);
            $decoded_json = json_decode($raw_json);

            $pages_json = $decoded_json->query->random;

            Cache::put($this->cacheKey, $pages_json, 3600);
        } catch (\Exception $ex) {
        }

        return $pages_json;
    }
}

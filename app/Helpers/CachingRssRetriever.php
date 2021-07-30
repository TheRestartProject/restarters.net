<?php

namespace App\Helpers;

use Cache;
use SimpleXMLElement;

class CachingRssRetriever
{
    protected $cacheKey;
    protected $feedLocation;

    public function __construct($feedLocation)
    {
        $this->cacheKey = 'rss_pages';
        $this->feedLocation = $feedLocation;
    }

    public function getRSSFeed($num_posts = 3)
    {
        if (Cache::has($this->cacheKey)) {
            return Cache::get($this->cacheKey);
        }

        $news_feed = [];
        try {
            $xml = new SimpleXMLElement(file_get_contents($this->feedLocation));
            $i = 0;

            foreach ($xml->channel->item as $xml_item) {
                $newsItem = new \stdClass;
                $newsItem->link = (string) ($xml_item->link);
                $newsItem->title = (string) ($xml_item->title);
                $news_feed[$i] = $newsItem;

                $i += 1;
                if ($i == $num_posts) {
                    break;
                }
            }

            Cache::put($this->cacheKey, $news_feed, 3600);
        } catch (\Exception $ex) {
        }

        return $news_feed;
    }
}

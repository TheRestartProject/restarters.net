<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Auth;

class DiscourseService {
    public function getDiscussionTopics($tag = null, $numberOfTopics = 5)
    {
        $topics = [];

        try {
            $client = app('discourse-client');

            $endpoint = $tag ? "/tag/{$tag}/l/latest.json" : "/latest.json";
            $response = $client->request('GET', $endpoint);
            $discourseResult = json_decode($response->getBody());

            $topics = $discourseResult->topic_list->topics;
            if (!empty($numberOfTopics)) {
                $topics = array_slice($topics, 0, $numberOfTopics, true);
            }

            $endpoint = "/site.json";
            $response = $client->request('GET', $endpoint);
            $discourseResult = json_decode($response->getBody());
            $categories = $discourseResult->categories;

            foreach ($topics as $topic) {
                foreach ($categories as $category) {
                    if ($topic->category_id == $category->id) {
                        $topic->category = $category;
                    }
                }
            }
        } catch (\Exception $ex) {
            Log::error("Error retrieving discussion topics" . $ex->getMessage());
        }

        return $topics;
    }
}
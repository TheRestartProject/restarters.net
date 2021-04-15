<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Auth;

class DiscourseService
{
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

    public function getUserIdsByBadge($badgeId)
    {
        $externalUserIds = [];

        try {
            $client = app('discourse-client');

            $endpoint = "/user_badges.json?badge_id={$badgeId}";
            $response = $client->request('GET', $endpoint);
            if ($response->getStatusCode() == 404) {
                Log::error("{$endpoint} not found");
                throw new \Exception("{$endpoint} not found");
            }
            $discourseResult = json_decode($response->getBody());

            $users = $discourseResult->users;

            foreach ($users as $user) {
                $endpoint = "/admin/users/{$user->id}.json";
                $response = $client->request('GET', $endpoint);
                $discourseResult = json_decode($response->getBody());
                $externalUserIds[] = [
                    'external_id' => $discourseResult->single_sign_on_record->external_id,
                    'username' => $discourseResult->single_sign_on_record->external_username,
                ];
                $this->avoidRateLimiting();
            }
        } catch (\Exception $ex) {
            Log::error("Error retrieving users by badge: " . $ex->getMessage());
        }

        return $externalUserIds;
    }

    protected function avoidRateLimiting()
    {
        // Sleep to avoid Discourse rate limiting of 60 requests per minute.
        // See https://meta.discourse.org/t/global-rate-limits-and-throttling-in-discourse/78612
        // There's probably a better way of doing this.
        sleep(1);
    }
}

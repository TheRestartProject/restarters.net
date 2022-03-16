<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DiscourseService;
use App\User;
use Auth;
use Illuminate\Http\Request;
use Cache;

class DiscourseController extends Controller
{
    /**
     * Get top Talk topics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $tag
     * @return \Illuminate\Http\Response
     */
    public function discussionTopics(Request $request, DiscourseService $discourseService, $tag = NULL)
    {
        $topics = [];

        $key = $tag ? "discourse_topics_$tag" : 'discourse_topics';

        if (config('restarters.features.discourse_integration')) {
            if (Cache::has($key)) {
                $topics = Cache::get($key);
            } else {
                $topics = $discourseService->getDiscussionTopics($tag);
                Cache::put($key, $topics, 60);
            }
        }

        return response()->json([
                                    'success' => 'success',
                                    'topics' => $topics
                                ], 200);
    }
}

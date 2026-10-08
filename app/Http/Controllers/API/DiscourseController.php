<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
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
     * @OA\Get(
     *      path="/api/talk/topics/{tag}",
     *      operationId="getDiscussionTopics",
     *      tags={"Discourse"},
     *      summary="Get top Restarters Talk (Discourse) topics",
     *      description="Public - doesn't need authentication. Used by the Nuxt dashboard's 'What's happening' panel. Returns [] if the restarters.features.discourse_integration feature flag is off, or if the call to Discourse fails (errors are logged, not thrown). Results are cached for 60 seconds per tag under the key discourse_topics[_{tag}].",
     *      @OA\Parameter(
     *          name="tag",
     *          description="Optional Discourse tag slug to filter topics by. Omit for the site-wide latest topics.",
     *          required=false,
     *          in="path",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="string", example="success"),
     *              @OA\Property(
     *                  property="topics",
     *                  type="array",
     *                  description="Raw Discourse topic objects (passed through from Discourse's /latest.json or /tag/{tag}/l/latest.json), each enriched with an embedded 'category' object matched from Discourse's /site.json. Empty if the discourse_integration feature is disabled or the upstream call fails.",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="slug", type="string"),
     *                      @OA\Property(property="posts_count", type="integer"),
     *                      @OA\Property(property="reply_count", type="integer"),
     *                      @OA\Property(property="created_at", type="string", format="date-time"),
     *                      @OA\Property(property="last_posted_at", type="string", format="date-time", nullable=true),
     *                      @OA\Property(property="category_id", type="integer"),
     *                      @OA\Property(
     *                          property="category",
     *                          type="object",
     *                          description="Merged in from Discourse's /site.json where category.id == topic.category_id",
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string"),
     *                          @OA\Property(property="slug", type="string"),
     *                          @OA\Property(property="color", type="string")
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function discussionTopics(Request $request, DiscourseService $discourseService, string $tag = NULL): JsonResponse
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

<?php

namespace App\Http\Controllers;

use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MicrotaskingController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            $currentUserId = Auth::user()->id;

            $currentUserQuests = $this->getUserContributions($currentUserId)['quests'];
            $currentUserContributions = $this->getUserContributions($currentUserId)['contributions'];
        } else {
            // TODO: if anon, we display different element.
            $currentUserQuests = 0;
            $currentUserContributions = 0;
        }

        $tag = config('restarters.microtasking.discussion_tag');

        return view('microtasking.dashboard', [
            'totalContributions' => $this->getTotalContributions(),
            'currentUserQuests' => $currentUserQuests,
            'currentUserContributions' => $currentUserContributions,
            'topics' => $this->getDiscussionTopics($tag, 5),
            'seeAllTopicsLink' => env('DISCOURSE_URL') . "/tag/{$tag}/l/latest"
        ]);
    }

    private function getUserContributions($userId)
    {
        $faultCatContributions = DB::select('select count(*) as total from devices_faults_opinions where user_id = :userId', ['userId' => $userId])[0]->total;
        $miscCatContributions = DB::select('select count(*) as total from devices_misc_opinions where user_id = :userId', ['userId' => $userId])[0]->total;
        $mobifixContributions = DB::select('select count(*) as total from devices_faults_mobiles_opinions where user_id = :userId', ['userId' => $userId])[0]->total;

        $quests = 0;
        if ($faultCatContributions > 0) {
            $quests++;
        }
        if ($miscCatContributions > 0) {
            $quests++;
        }
        if ($mobifixContributions > 0) {
            $quests++;
        }

        return [
            'quests' => $quests,
            'contributions' => $faultCatContributions + $miscCatContributions + $mobifixContributions
        ];
    }

    private function getTotalContributions()
    {
        $faultCatContributions = DB::select('select count(*) as total from devices_faults_opinions')[0]->total;
        $miscCatContributions = DB::select('select count(*) as total from devices_misc_opinions')[0]->total;
        $mobifixContributions = DB::select('select count(*) as total from devices_faults_mobiles_opinions')[0]->total;

        return $faultCatContributions + $miscCatContributions + $mobifixContributions;
    }

    private function getDiscussionTopics($tag, $numberOfTopics = null)
    {
        if (!Auth::check()) {
            return [];
        }

        $topics = [];

        try {
            $username = Auth::user()->username;
            $client = app('discourse-client', ['username' => $username]);

            $endpoint = "/tag/{$tag}/l/latest.json";
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
            Log::error("Error retrieving microtasking discussion topics for username '{$username}': " . $ex->getMessage());
        }

        return $topics;
    }
}

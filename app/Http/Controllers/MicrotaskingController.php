<?php

namespace App\Http\Controllers;

use App\Faultcat;
use App\Misccat;
use App\Mobifix;
use App\MobifixOra;
use App\PrintcatOra;
use App\TabicatOra;

use App\Services\DiscourseService;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MicrotaskingController extends Controller
{
    protected $quests;

    public function __construct()
    {
        $this->quests = [
            new Faultcat,
            new Misccat,
            new Mobifix,
            new MobifixOra,
            new PrintcatOra,
            new TabicatOra
        ];
    }

    public function index(DiscourseService $discourseService)
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

        $activeQuest = config('restarters.microtasking.active_quest');
        $tag = config('restarters.microtasking.discussion_tag');

        return view('microtasking.dashboard', [
            'totalQuests' => $this->getTotalContributions()['quests'],
            'totalContributions' => $this->getTotalContributions()['contributions'],
            'currentUserQuests' => $currentUserQuests,
            'currentUserContributions' => $currentUserContributions,
            'topics' => $discourseService->getDiscussionTopics($tag, 5),
            'seeAllTopicsLink' => env('DISCOURSE_URL') . "/tag/{$tag}/l/latest",
            'activeQuest' => $activeQuest,
        ]);
    }

    private function getUserContributions($userId)
    {
        $userQuests = 0;
        $userContributions = 0;

        foreach ($this->quests as $quest) {
            $questContributions = $quest->where('user_id', $userId)->count();
            if ($questContributions > 0) {
                $userQuests++;
                $userContributions += $questContributions;
            }
        }

        return [
            'quests' => $userQuests,
            'contributions' => $userContributions
        ];
    }

    private function getTotalContributions()
    {
        $totalQuests = 0;
        $totalContributions = 0;

        foreach ($this->quests as $quest) {
            $totalQuests++;
            $totalContributions += $quest->count();
        }

        return [
            'quests' => $totalQuests,
            'contributions' => $totalContributions
        ];
    }
}

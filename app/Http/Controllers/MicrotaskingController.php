<?php

namespace App\Http\Controllers;

use App\BattcatOra;
use App\DustupOra;
use App\Faultcat;
use App\Misccat;
use App\Mobifix;
use App\MobifixOra;
use App\PrintcatOra;
use App\Services\DiscourseService;
use App\TabicatOra;
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
            new TabicatOra,
            new BattcatOra,
            new DustupOra,
        ];
    }

    public function index(DiscourseService $discourseService, Request $request)
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

        // We record that we have visited this page, so that if we subsequently sign up, we can redirect back to it.
        // This is an intentionally partial solution to the problem of redirecting after we log in.
        $request->session()->put('redirectTime', time());
        $request->session()->put('redirectTo', $request->path());

        return view('microtasking.dashboard', [
            'totalQuests' => $this->getTotalContributions()['quests'],
            'totalContributions' => $this->getTotalContributions()['contributions'],
            'currentUserQuests' => $currentUserQuests,
            'currentUserContributions' => $currentUserContributions,
            'topics' => $discourseService->getDiscussionTopics($tag, 5),
            'tag' => $tag,
            'seeAllTopicsLink' => env('DISCOURSE_URL')."/tag/{$tag}/l/latest",
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
            'contributions' => $userContributions,
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
            'contributions' => $totalContributions,
        ];
    }
}

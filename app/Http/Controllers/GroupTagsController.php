<?php

namespace App\Http\Controllers;

use App\GroupTags;
use App\Helpers\Fixometer;
use App\Http\Resources\Tag;
use Auth;

class GroupTagsController extends Controller
{
    /**
     * Render the global group-tags admin page (a Vue SPA that talks to
     * /api/v2/group-tags). Network-scoped tags are managed elsewhere.
     */
    public function index($editId = null)
    {
        $user = Auth::user();

        if (! Fixometer::hasRole($user, 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $tags = GroupTags::global()->orderBy('tag_name', 'asc')->get();
        $tagsForVue = $tags->map(fn ($tag) => (new Tag($tag))->toArray(request()))->values();

        return view('tags.index', [
            'title' => __('group-tags.title'),
            'tags' => $tags,
            'tagsForVue' => $tagsForVue,
            'apiToken' => $user->api_token,
            'editId' => $editId !== null ? (int) $editId : null,
        ]);
    }
}

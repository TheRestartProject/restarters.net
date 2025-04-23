<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use App\Models\GroupTags;
use App\Helpers\Fixometer;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class GroupTagsController extends Controller
{
    public function index()
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $all_tags = GroupTags::all();

        return view('tags.index', [
        'title' => __('group-tags.title'),
        'tags' => $all_tags,
        ]);
    }

    public function postCreateTag(Request $request): RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $name = $request->input('tag-name');
        $description = $request->input('tag-description');

        $group_tag = GroupTags::create([
        'tag_name'    => $name,
        'description' => $description,
        ]);

        return Redirect::to('tags/edit/'.$group_tag->id)->with('success', __('group-tags.create_success'));
    }

    public function getEditTag($id)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $tag = GroupTags::find($id);

        return view('tags.edit', [
        'title' => __('group-tags.edit_tag'),
        'tag'   => $tag,
        ]);
    }

    public function postEditTag($id, Request $request): RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $name = $request->input('tag-name');
        $description = $request->input('tag-description');

        GroupTags::find($id)->update([
        'tag_name'    => $name,
        'description' => $description,
        ]);

        return Redirect::back()->with('success', __('group-tags.update_success'));
    }

    public function getDeleteTag($id): RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        GroupTags::find($id)->delete();

        return Redirect::to('/tags')->with('success', __('group-tags.delete_success'));
    }
}

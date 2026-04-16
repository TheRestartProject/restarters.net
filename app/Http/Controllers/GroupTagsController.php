<?php

namespace App\Http\Controllers;

use App\Attributes\Feature;
use App\Attributes\UserStory;
use App\GroupTags;
use App\Helpers\Fixometer;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

#[Feature('Administration', description: 'Platform administration and configuration')]
class GroupTagsController extends Controller
{
    #[UserStory('As an Admin, I can view all group tags', persona: 'Admin', theme: 'Reference data')]
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

    #[UserStory('As an Admin, I can create a new group tag', persona: 'Admin', theme: 'Reference data')]
    public function postCreateTag(Request $request)
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

    #[UserStory('As an Admin, I can access the form to edit a group tag', persona: 'Admin', theme: 'Reference data')]
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

    #[UserStory('As an Admin, I can update a group tag', persona: 'Admin', theme: 'Reference data')]
    public function postEditTag($id, Request $request)
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

    #[UserStory('As an Admin, I can delete a group tag', persona: 'Admin', theme: 'Reference data')]
    public function getDeleteTag($id)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        GroupTags::find($id)->delete();

        return Redirect::to('/tags')->with('success', __('group-tags.delete_success'));
    }
}

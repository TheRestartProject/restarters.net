<?php

namespace App\Http\Controllers;

use App\GroupTags;
use Auth;
use App\Helpers\Fixometer;
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
        'title' => 'Group Tags',
        'tags' => $all_tags,
        ]);
    }

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

        return Redirect::to('tags/edit/'.$group_tag->id)->with('success', 'Group Tag successfully created!');
    }

    public function getEditTag($id)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $tag = GroupTags::find($id);

        return view('tags.edit', [
        'title' => 'Edit Group Tag',
        'tag'   => $tag,
        ]);
    }

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

        return Redirect::back()->with('success', 'Group Tag successfully updated!');
    }

    public function getDeleteTag($id)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        GroupTags::find($id)->delete();

        return Redirect::to('/tags')->with('success', 'Group Tag successfully deleted!');
    }
}

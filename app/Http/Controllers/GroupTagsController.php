<?php

namespace App\Http\Controllers;

use App\GroupTags;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class GroupTagsController extends Controller
{

  public function index() {
    $all_tags = GroupTags::all();

    return view('tags.index', [
      'title' => 'Group Tags',
      'tags' => $all_tags
    ]);
  }

  public function getCreateTag() {

    return view('tags.create', [
      'title' => 'Add Group Tag',
    ]);

  }

  public function postCreateTag(Request $request) {

    $name = $request->input('tag-name');
    $description = $request->input('tag-description');

    $group_tag = GroupTags::create([
      'tag_name'    => $name,
      'description' => $description
    ]);

    return Redirect::to('tags/edit/'.$tag->id);

  }

  public function getEditTag($id) {

    $tag = GroupTags::find($id);

    return view('tags.edit', [
      'title' => 'Edit Group Tag',
      'tag'   => $tag,
    ]);

  }

  public function postEditTag($id, Request $request) {

    $name = $request->input('tag-name');
    $description = $request->input('tag-description');

    GroupTags::find($id)->update([
      'tag_name'    => $name,
      'description' => $description
    ]);

    return Redirect::back()->with('message', 'Group Tag updated!');

  }

  public function getDeleteTag($id) {

    GroupTags::find($id)->delete();

    return Redirect::back()->with('message', 'Group Tag deleted!');

  }

}

<?php

namespace App\Http\Controllers;

use Auth;
use App\GroupTags;
use FixometerHelper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class GroupTagsController extends Controller
{

  public function index() {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    $all_tags = GroupTags::all();

    return view('tags.index', [
      'title' => 'Group Tags',
      'tags' => $all_tags
    ]);

  }

  public function getCreateTag() {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    return view('tags.create', [
      'title' => 'Add Group Tag',
    ]);

  }

  public function postCreateTag(Request $request) {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    $name = $request->input('tag-name');
    $description = $request->input('tag-description');

    $group_tag = GroupTags::create([
      'tag_name'    => $name,
      'description' => $description
    ]);

    return Redirect::to('tags/edit/'.$group_tag->id);

  }

  public function getEditTag($id) {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    $tag = GroupTags::find($id);

    return view('tags.edit', [
      'title' => 'Edit Group Tag',
      'tag'   => $tag,
    ]);

  }

  public function postEditTag($id, Request $request) {
    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    $name = $request->input('tag-name');
    $description = $request->input('tag-description');

    GroupTags::find($id)->update([
      'tag_name'    => $name,
      'description' => $description
    ]);

    return Redirect::back()->with('message', 'Group Tag updated!');

  }

  public function getDeleteTag($id) {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    GroupTags::find($id)->delete();

    return Redirect::to('/tags')->with('message', 'Group Tag deleted!');

  }

}

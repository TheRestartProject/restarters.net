<?php

namespace App\Http\Controllers;

use Auth;
use App\Skills;
use FixometerHelper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SkillsController extends Controller
{

  public function index() {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    $all_skills = Skills::all();

    return view('skills.index', [
      'title' => 'Skills',
      'skills' => $all_skills
    ]);
  }

  public function getCreateSkill() {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    return view('skills.create', [
      'title' => 'Add Skill',
    ]);

  }

  public function postCreateSkill(Request $request) {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    $skill = Skills::create([
      'skill_name'  => $request->input('skill_name'),
      // 'category'    => $request->input('category'),
      'description' => $request->input('skill_desc')
    ]);

    return Redirect::to('skills/edit/'.$skill->id);

  }

  public function getEditSkill($id) {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    $skill = Skills::find($id);

    return view('skills.edit', [
      'title' => 'Edit Skill',
      'skill' => $skill,
    ]);

  }

  public function postEditSkill($id, Request $request) {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    Skills::find($id)->update([
      'skill_name'  => $request->input('skill-name'),
      'category'  => $request->input('category'),
      'description' => $request->input('skill-description')
    ]);

    return Redirect::back()->with('message', 'Skill updated!');

  }

  public function getDeleteSkill($id) {

    if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      return redirect('/user/forbidden');

    Skills::find($id)->delete();

    return Redirect::to('/skills')->with('message', 'Skill deleted!');

  }

}

<?php

namespace App\Http\Controllers;

use App\Skills;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SkillsController extends Controller
{

  public function index() {
    $all_skills = Skills::all();

    return view('skills.index', [
      'title' => 'Skills',
      'skills' => $all_skills
    ]);
  }

  public function getCreateSkill() {

    return view('skills.create', [
      'title' => 'Add Skill',
    ]);

  }

  public function postCreateSkill(Request $request) {

    $name = $request->input('skill-name');
    $description = $request->input('skill-description');

    $skill = Skills::create([
      'skill_name'  => $name,
      'description' => $description
    ]);

    return Redirect::to('skills/edit/'.$skill->id);

  }

  public function getEditSkill($id) {

    $skill = Skills::find($id);

    return view('skills.edit', [
      'title' => 'Edit Skill',
      'skill' => $skill,
    ]);

  }

  public function postEditSkill($id, Request $request) {

    $name = $request->input('skill-name');
    $description = $request->input('skill-description');

    Skills::find($id)->update([
      'skill_name'    => $name,
      'description' => $description
    ]);

    return Redirect::back()->with('message', 'Skill updated!');

  }

  public function getDeleteSkill($id) {

    Skills::find($id)->delete();

    return Redirect::back()->with('message', 'Skill deleted!');

  }

}

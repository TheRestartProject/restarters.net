<?php

namespace App\Http\Controllers;

use App\Skills;
use App\UsersSkills;
use Auth;
use App\Helpers\Fixometer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SkillsController extends Controller
{
    public function index()
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $all_skills = Skills::all();

        return view('skills.index', [
        'title' => 'Skills',
        'skills' => $all_skills,
        ]);
    }

    public function postCreateSkill(Request $request)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $skill = Skills::create([
        'skill_name'  => $request->input('skill_name'),
        // 'category'    => $request->input('category'),
        'description' => $request->input('skill_desc'),
        ]);

        return Redirect::to('skills/edit/'.$skill->id)->with('success', 'Skill successfully created!');
    }

    public function getEditSkill($id)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $skill = Skills::find($id);

        return view('skills.edit', [
        'title' => 'Edit Skill',
        'skill' => $skill,
        ]);
    }

    public function postEditSkill($id, Request $request)
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        Skills::find($id)->update([
        'skill_name'  => $request->input('skill-name'),
        'category'  => $request->input('category'),
        'description' => $request->input('skill-description'),
        ]);

        return Redirect::back()->with('success', 'Skill successfully updated!');
    }

    public function getDeleteSkill($id)
    {

      // Are you an admin?
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        // If we have permission, let's delete
        $skill = Skills::find($id)->delete();

        // We can only delete the data in the pivot table if the delete was successful
        if ($skill == 1) {
            UsersSkills::where('skill', $id)->delete();
        }

        // Then redirect back
        return Redirect::to('/skills')->with('success', 'Skill successfully deleted!');
    }
}

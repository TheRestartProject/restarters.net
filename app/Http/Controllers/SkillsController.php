<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use App\Helpers\Fixometer;
use App\Models\Skills;
use App\Models\UsersSkills;
use Auth;
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

    public function postCreateSkill(Request $request): RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $skill = Skills::create([
        'skill_name'  => $request->input('skill_name'),
        'description' => $request->input('skill_desc'),
        ]);

        return Redirect::to('skills/edit/'.$skill->id)->with('success', __('skills.create_success'));
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

    public function postEditSkill($id, Request $request): RedirectResponse
    {
        if (! Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return redirect('/user/forbidden');
        }

        Skills::find($id)->update([
        'skill_name'  => $request->input('skill-name'),
        'category'  => $request->input('category'),
        'description' => $request->input('skill-description'),
        ]);

        return Redirect::back()->with('success', __('skills.update_success'));
    }

    public function getDeleteSkill($id): RedirectResponse
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
        return Redirect::to('/skills')->with('success', __('skills.delete_success'));
    }
}

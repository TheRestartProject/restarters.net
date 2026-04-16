<?php

namespace App\Http\Controllers;

use App\Attributes\Feature;
use App\Attributes\UserStory;
use App\Helpers\Fixometer;
use App\Skills;
use App\UsersSkills;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

#[Feature('Administration', description: 'Platform administration and configuration')]
class SkillsController extends Controller
{
    #[UserStory('As an Admin, I can view all repair skills', persona: 'Admin')]
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

    #[UserStory('As an Admin, I can create a new repair skill', persona: 'Admin')]
    public function postCreateSkill(Request $request)
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

    #[UserStory('As an Admin, I can access the form to edit a repair skill', persona: 'Admin')]
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

    #[UserStory('As an Admin, I can update a repair skill', persona: 'Admin')]
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

        return Redirect::back()->with('success', __('skills.update_success'));
    }

    #[UserStory('As an Admin, I can delete a repair skill', persona: 'Admin')]
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
        return Redirect::to('/skills')->with('success', __('skills.delete_success'));
    }
}

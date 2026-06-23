<?php

namespace App\Http\Controllers;

use App\Helpers\Fixometer;
use App\Skills;
use Auth;

class SkillsController extends Controller
{
    /**
     * Render the skills admin page (a Vue SPA that talks to /api/v2/skills).
     * All create/edit/delete now goes through the API.
     *
     * @param  int|null  $editId  Optional skill id to pre-open in the edit modal
     *                            (used by the legacy /skills/edit/{id} bookmark).
     */
    public function index($editId = null)
    {
        $user = Auth::user();

        if (! Fixometer::hasRole($user, 'Administrator')) {
            return redirect('/user/forbidden');
        }

        $all_skills = Skills::orderBy('skill_name', 'asc')->get();

        $skillsForVue = $all_skills->map(function ($skill) {
            return [
                'id' => $skill->id,
                'skill_name' => $skill->skill_name,
                'category' => $skill->category !== null ? (int) $skill->category : null,
                'description' => $skill->description,
            ];
        })->values();

        return view('skills.index', [
            'title' => 'Skills',
            'skills' => $all_skills,
            'skillsForVue' => $skillsForVue,
            'skillCategories' => Fixometer::skillCategories(),
            'apiToken' => $user->api_token,
            'editId' => $editId !== null ? (int) $editId : null,
        ]);
    }
}

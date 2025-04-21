<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Role;
use App\Skills;
use App\User;
use App\UsersSkills;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Volunteer",
 *     schema="Volunteer",
 *     description="A volunteer on a group or event.",
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this record (i.e. the event/group and user combination)",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *          property="user",
 *          title="user",
 *          description="Unique identifier of this volunteer's user record",
 *          format="int64",
 *          example=2
 *     ),
 *     @OA\Property(
 *          property="name",
 *          title="name",
 *          description="The volunteer's name",
 *          format="string",
 *          example="Sam"
 *     ),
 *     @OA\Property(
 *          property="host",
 *          title="host",
 *          description="Whether the volunteer is a host of the event/group",
 *          format="boolean",
 *          example="false"
 *     ),
 *     @OA\Property(
 *          property="image",
 *          title="image",
 *          description="URL of an image for this user.  You should prefix this with /uploads before use.",
 *          format="string",
 *          example="/mid_1597853610178a4b76e4d666b2a7b32ee75d8a24c706f1cbf213970.png"
 *     ),
 *     @OA\Schema(
 *         title="SkillCollection",
 *         schema="SkillCollection",
 *         description="A collection of skills possessed by this volunteer.",
 *         type="array",
 *         @OA\Items(
 *             ref="#/components/schemas/Skill"
 *         )
 *     )
 * )
 */

class Volunteer extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        if (\Cache::has('all_skills')) {
            $allSkills = \Cache::get('all_skills');
        } else {
            // We extract some skills info in bulk to reduce the number of distinct queries on groups with many
            // volunteers.
            $allSkills = Skills::all()->all();
            $ix = [];
            foreach ($allSkills as $i => $skill) {
                $ix[$skill->id] = $skill;
                $ix[$skill->id]->skillName;
            }

            \Cache::put('all_skills', $ix, 7200);
        }

        $volunteerSkills = UsersSkills::where('user', $this->user)->get();

        $skills = [];
        foreach ($volunteerSkills as $volunteerSkill) {
            if (array_key_exists($volunteerSkill->skill, $allSkills)) {
                $skills[] = $allSkills[$volunteerSkill->skill];
            }
        }

        $image = User::getProfile($this->user)->path;
        $image = $image ? "/uploads/thumbnail_$image" : "/images/placeholder-avatar.png";

        $u = User::find($this->user);

        return [
            'id' => $this->idusers_groups, // When we write the v2 event volunteer code we'll need to change this.
            'user' => $this->user,
            'group' => $this->group,
            'name' => $u->name,
            'host' => $this->role == Role::HOST,
            'image' => $image,
            'skills' => SkillCollection::make($skills)
        ];
    }
}

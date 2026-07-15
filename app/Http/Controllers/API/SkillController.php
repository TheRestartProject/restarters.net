<?php

namespace App\Http\Controllers\API;

use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Http\Resources\Skill;
use App\Http\Resources\SkillCollection;
use App\Skills;
use App\UsersSkills;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SkillController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/skills",
     *      operationId="listSkillsv2",
     *      tags={"Skills"},
     *      summary="List Skills",
     *      description="Returns all volunteer skills, ordered alphabetically. Public endpoint.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Skill"))
     *          )
     *      )
     * )
     */
    public function listSkillsv2()
    {
        $skills = Skills::orderBy('skill_name', 'asc')->get();

        return SkillCollection::make($skills);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/skills/{id}",
     *      operationId="getSkillv2",
     *      tags={"Skills"},
     *      summary="Get a Skill",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Skill"))
     *      ),
     *      @OA\Response(response=404, description="Skill not found")
     * )
     */
    public function getSkillv2($id)
    {
        $skill = Skills::findOrFail($id);

        return Skill::make($skill);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/skills",
     *      operationId="createSkillv2",
     *      tags={"Skills"},
     *      summary="Create a Skill",
     *      description="Administrator only.",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"skill_name","category"},
     *              @OA\Property(property="skill_name", type="string", maxLength=255, example="Soldering"),
     *              @OA\Property(property="category", type="integer", description="1 = Organising, 2 = Technical", example=2),
     *              @OA\Property(property="description", type="string", nullable=true, example="Surface-mount component rework")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Skill created",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Skill"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function createSkillv2(Request $request): JsonResponse
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate($this->validationRules());

        $skill = Skills::create($validated);

        return response()->json(['data' => (new Skill($skill))->toArray($request)], 201);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/skills/{id}",
     *      operationId="updateSkillv2",
     *      tags={"Skills"},
     *      summary="Update a Skill",
     *      description="Administrator only.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"skill_name","category"},
     *              @OA\Property(property="skill_name", type="string", maxLength=255, example="Soldering"),
     *              @OA\Property(property="category", type="integer", example=2),
     *              @OA\Property(property="description", type="string", nullable=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Skill updated",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Skill"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Skill not found"),
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function updateSkillv2(Request $request, $id)
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $skill = Skills::findOrFail($id);

        $validated = $request->validate($this->validationRules($skill->id));

        $skill->update($validated);

        return Skill::make($skill->fresh());
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/skills/{id}",
     *      operationId="deleteSkillv2",
     *      tags={"Skills"},
     *      summary="Delete a Skill",
     *      description="Administrator only. Also removes any users_skills pivot rows referencing this skill.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=204, description="Skill deleted"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Skill not found")
     * )
     */
    public function deleteSkillv2($id)
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $skill = Skills::findOrFail($id);

        if ($skill->delete()) {
            UsersSkills::where('skill', $skill->id)->delete();
        }

        return response()->noContent();
    }

    private function validationRules($ignoreId = null): array
    {
        $allowedCategories = array_map('intval', array_keys(Fixometer::skillCategories()));
        $uniqueRule = Rule::unique('skills', 'skill_name');
        if ($ignoreId !== null) {
            $uniqueRule = $uniqueRule->ignore($ignoreId);
        }

        return [
            'skill_name' => ['required', 'string', 'max:255', $uniqueRule],
            'category' => ['required', 'integer', Rule::in($allowedCategories)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

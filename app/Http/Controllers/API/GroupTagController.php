<?php

namespace App\Http\Controllers\API;

use App\GroupTags;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Http\Resources\Tag;
use App\Http\Resources\TagCollection;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupTagController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/group-tags",
     *      operationId="listGroupTagsv2",
     *      tags={"GroupTags"},
     *      summary="List global group tags",
     *      description="Returns all global (cross-network) group tags, ordered alphabetically. Public endpoint. Network-scoped tags are exposed via /api/v2/networks/{id}/tags.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Tag"))
     *          )
     *      )
     * )
     */
    public function listGroupTagsv2()
    {
        $tags = GroupTags::global()->orderBy('tag_name', 'asc')->get();

        return TagCollection::make($tags);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/group-tags/{id}",
     *      operationId="getGroupTagv2",
     *      tags={"GroupTags"},
     *      summary="Get a global group tag",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Tag"))
     *      ),
     *      @OA\Response(response=404, description="Group tag not found (or is network-scoped)")
     * )
     */
    public function getGroupTagv2($id)
    {
        $tag = $this->findGlobalOrFail($id);

        return Tag::make($tag);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/group-tags",
     *      operationId="createGroupTagv2",
     *      tags={"GroupTags"},
     *      summary="Create a global group tag",
     *      description="Administrator only.",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", maxLength=255, example="Scotland"),
     *              @OA\Property(property="description", type="string", maxLength=1000, nullable=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Group tag created",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Tag"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function createGroupTagv2(Request $request): JsonResponse
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate($this->validationRules());

        $tag = GroupTags::create([
            'tag_name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'network_id' => null,
        ]);

        return response()->json(['data' => (new Tag($tag))->toArray($request)], 201);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/group-tags/{id}",
     *      operationId="updateGroupTagv2",
     *      tags={"GroupTags"},
     *      summary="Update a global group tag",
     *      description="Administrator only. Network-scoped tags must be updated via /api/v2/networks/{id}/tags.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", maxLength=255),
     *              @OA\Property(property="description", type="string", nullable=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Group tag updated",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Tag"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Group tag not found (or is network-scoped)"),
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function updateGroupTagv2(Request $request, $id)
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $tag = $this->findGlobalOrFail($id);

        $validated = $request->validate($this->validationRules($tag->id));

        $tag->update([
            'tag_name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return Tag::make($tag->fresh());
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/group-tags/{id}",
     *      operationId="deleteGroupTagv2",
     *      tags={"GroupTags"},
     *      summary="Delete a global group tag",
     *      description="Administrator only. Network-scoped tags must be deleted via /api/v2/networks/{id}/tags.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=204, description="Group tag deleted"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Group tag not found (or is network-scoped)")
     * )
     */
    public function deleteGroupTagv2($id)
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $tag = $this->findGlobalOrFail($id);
        $tag->delete();

        return response()->noContent();
    }

    /**
     * Look up a global group tag, or throw a ModelNotFoundException so the
     * caller cannot use this endpoint to reach into a network's tags.
     */
    private function findGlobalOrFail(int $id): GroupTags
    {
        return GroupTags::global()->findOrFail($id);
    }

    private function validationRules($ignoreId = null): array
    {
        // Uniqueness only within the global scope: a global tag and a
        // network-scoped tag can share a name (they live in different scopes).
        $uniqueRule = Rule::unique('group_tags', 'tag_name')->whereNull('network_id');
        if ($ignoreId !== null) {
            $uniqueRule = $uniqueRule->ignore($ignoreId);
        }

        return [
            'name' => ['required', 'string', 'max:255', $uniqueRule],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

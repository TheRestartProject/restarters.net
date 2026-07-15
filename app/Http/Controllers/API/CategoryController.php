<?php

namespace App\Http\Controllers\API;

use App\Category;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Http\Resources\Category as CategoryResource;
use App\Http\Resources\CategoryCollection;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/categories",
     *      operationId="listCategoriesv2",
     *      tags={"Categories"},
     *      summary="List Categories",
     *      description="Returns all device categories in the current revision, ordered by name. Includes joined cluster_name. Public endpoint.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Category"))
     *          )
     *      )
     * )
     */
    public function listCategoriesv2()
    {
        $categories = $this->categoriesWithClusterName()
            ->orderBy('categories.name', 'asc')
            ->get();

        return CategoryCollection::make($categories);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/categories/{id}",
     *      operationId="getCategoryv2",
     *      tags={"Categories"},
     *      summary="Get a Category",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Category"))
     *      ),
     *      @OA\Response(response=404, description="Category not found")
     * )
     */
    public function getCategoryv2($id)
    {
        $category = $this->categoriesWithClusterName()
            ->where('categories.idcategories', $id)
            ->firstOrFail();

        return CategoryResource::make($category);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/categories/{id}",
     *      operationId="updateCategoryv2",
     *      tags={"Categories"},
     *      summary="Update a Category",
     *      description="Administrator only.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", maxLength=255),
     *              @OA\Property(property="weight", type="number", format="float", nullable=true),
     *              @OA\Property(property="footprint", type="number", format="float", nullable=true),
     *              @OA\Property(property="footprint_reliability", type="integer", minimum=1, maximum=6, nullable=true),
     *              @OA\Property(property="cluster", type="integer", nullable=true),
     *              @OA\Property(property="description_short", type="string", nullable=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Category updated",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Category"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Category not found"),
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function updateCategoryv2(Request $request, $id)
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'footprint' => ['nullable', 'numeric', 'min:0'],
            'footprint_reliability' => ['nullable', 'integer', Rule::in([1, 2, 3, 4, 5, 6])],
            'cluster' => ['nullable', 'integer'],
            'description_short' => ['nullable', 'string'],
        ]);

        $category->update($validated);

        $fresh = $this->categoriesWithClusterName()
            ->where('categories.idcategories', $category->idcategories)
            ->firstOrFail();

        return CategoryResource::make($fresh);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/category-clusters",
     *      operationId="listCategoryClustersv2",
     *      tags={"Categories"},
     *      summary="List category clusters",
     *      description="Returns the cluster table (parent groupings for categories). Public endpoint, used to populate the cluster dropdown on the admin page.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="Computers and Home Office")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function listCategoryClustersv2(): JsonResponse
    {
        $rows = DB::select('SELECT idclusters AS id, name FROM clusters ORDER BY idclusters ASC');

        return response()->json([
            'data' => array_map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name], $rows),
        ]);
    }

    /**
     * Build the base query for categories joined with their cluster name.
     * Scopes to the current revision (matches the legacy admin views).
     */
    private const CURRENT_REVISION = 2;

    private function categoriesWithClusterName()
    {
        return Category::query()
            ->select('categories.*', 'clusters.name as cluster_name')
            ->leftJoin('clusters', 'clusters.idclusters', '=', 'categories.cluster')
            ->where('categories.revision', self::CURRENT_REVISION);
    }
}

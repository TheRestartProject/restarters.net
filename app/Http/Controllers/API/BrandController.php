<?php

namespace App\Http\Controllers\API;

use App\Attributes\Feature;
use App\Attributes\UserStory;
use App\Brands;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Http\Resources\Brand;
use App\Http\Resources\BrandCollection;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Feature('Administration', description: 'Platform administration and configuration')]
class BrandController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/brands",
     *      operationId="listBrandsv2",
     *      tags={"Brands"},
     *      summary="List Brands",
     *      description="Returns all device brands, ordered alphabetically. Public endpoint.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/Brand")
     *              )
     *          )
     *      )
     * )
     */
    #[UserStory('As an Admin, I can view all brands', persona: 'Admin', theme: 'Reference data')]
    public function listBrandsv2()
    {
        $brands = Brands::orderBy('brand_name', 'asc')->get();

        return BrandCollection::make($brands);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/brands/{id}",
     *      operationId="getBrandv2",
     *      tags={"Brands"},
     *      summary="Get a Brand",
     *      description="Returns a single brand by id.",
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", ref="#/components/schemas/Brand")
     *          )
     *      ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    #[UserStory('As an Admin, I can view a brand', persona: 'Admin', theme: 'Reference data')]
    public function getBrandv2($id)
    {
        $brand = Brands::findOrFail($id);

        return Brand::make($brand);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/brands",
     *      operationId="createBrandv2",
     *      tags={"Brands"},
     *      summary="Create a Brand",
     *      description="Create a new device brand. Administrator only.",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"brand_name"},
     *              @OA\Property(property="brand_name", type="string", maxLength=255, example="Sony")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Brand created",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", ref="#/components/schemas/Brand")
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    #[UserStory('As an Admin, I can create a new brand', persona: 'Admin', theme: 'Reference data')]
    public function createBrandv2(Request $request): JsonResponse
    {
        if ($resp = $this->requireAdministrator()) {
            return $resp;
        }

        $validated = $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name',
        ]);

        $brand = Brands::create($validated);

        return response()->json(['data' => (new Brand($brand))->toArray($request)], 201);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/brands/{id}",
     *      operationId="updateBrandv2",
     *      tags={"Brands"},
     *      summary="Update a Brand",
     *      description="Update a brand. Administrator only.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"brand_name"},
     *              @OA\Property(property="brand_name", type="string", maxLength=255, example="Sony")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Brand updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", ref="#/components/schemas/Brand")
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    #[UserStory('As an Admin, I can edit a brand', persona: 'Admin', theme: 'Reference data')]
    public function updateBrandv2(Request $request, $id)
    {
        if ($resp = $this->requireAdministrator()) {
            return $resp;
        }

        $brand = Brands::findOrFail($id);

        $validated = $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name,' . $brand->id,
        ]);

        $brand->update($validated);

        return Brand::make($brand->fresh());
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/brands/{id}",
     *      operationId="deleteBrandv2",
     *      tags={"Brands"},
     *      summary="Delete a Brand",
     *      description="Delete a brand. Administrator only.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=204, description="Brand deleted"),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    #[UserStory('As an Admin, I can delete a brand', persona: 'Admin', theme: 'Reference data')]
    public function deleteBrandv2($id)
    {
        if ($resp = $this->requireAdministrator()) {
            return $resp;
        }

        $brand = Brands::findOrFail($id);
        $brand->delete();

        return response()->noContent();
    }
}

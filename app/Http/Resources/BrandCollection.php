<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="BrandCollection",
 *     schema="BrandCollection",
 *     description="A collection of brands.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/Brand"
 *     )
 * )
*/
class BrandCollection extends ResourceCollection
{
    public $collects = \App\Http\Resources\Brand::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}

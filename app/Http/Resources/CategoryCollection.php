<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="CategoryCollection",
 *     schema="CategoryCollection",
 *     description="A collection of device categories.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/Category"
 *     )
 * )
*/
class CategoryCollection extends ResourceCollection
{
    public $collects = \App\Http\Resources\Category::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}

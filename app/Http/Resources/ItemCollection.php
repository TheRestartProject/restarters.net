<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="ItemCollection",
 *     schema="ItemCollection",
 *     description="A collection of items.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/Item"
 *     )
 * )
 */

class ItemCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}

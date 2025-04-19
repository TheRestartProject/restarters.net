<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="GroupCollection",
 *     schema="GroupCollection",
 *     description="A collection of groups.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/Group"
 *     )
 * )
*/

class GroupCollection extends ResourceCollection
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

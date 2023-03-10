<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="TagCollection",
 *     schema="TagCollection",
 *     description="A collection of group tags.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/Tag"
 *     )
 * )
 */

class TagCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

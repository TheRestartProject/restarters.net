<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="SkillCollection",
 *     schema="SkillCollection",
 *     description="A collection of volunteer skills.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/Skill"
 *     )
 * )
 */

class SkillCollection extends ResourceCollection
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

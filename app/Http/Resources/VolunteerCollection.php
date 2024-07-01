<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="VolunteerCollection",
 *     schema="VolunteerCollection",
 *     description="A collection of volunteers for a group or event.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/Volunteer"
 *     )
 * )
 */

class VolunteerCollection extends ResourceCollection
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

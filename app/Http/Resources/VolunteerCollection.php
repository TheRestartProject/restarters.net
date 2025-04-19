<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
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
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}

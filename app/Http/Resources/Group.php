<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="GroupResponse",
 *     schema="GroupResponse",
 *     description="A response containing a group",
 *     @OA\Xml(
 *         name="GroupResponse"
 *     ),
 * )
*/
class Group extends JsonResource
{
    /**
     * @OA\Property(
     *     title="Data",
     *     description="Data wrapper"
     * )
     *
     *  @var \App\Group
     */
    private $data;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $stats = $this->resource->getGroupStats();
        $stats['events'] = $stats['parties'];
        unset($stats['parties']);

        return [
            'id' => $this->idgroups,
            'name' => $this->name,
            'image' => $this->groupImage && is_object($this->groupImage) && is_object($this->groupImage->image) ? $this->groupImage->image->path : null,
            'area' => $this->area,
            'country' => $this->country,
            'website' => $this->website,
            'description' => $this->free_text,
            'stats' => $stats,
            'updated_at' => Carbon::parse($this->updated_at)->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Tag",
 *     schema="Tag",
 *     description="A tag to record something about a group.",
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this tag",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *          property="name",
 *          title="name",
 *          description="Unique name of this tag",
 *          format="string",
 *          example="Scotland"
 *     ),
 *     @OA\Property(
 *          property="description",
 *          title="description",
 *          description="What this tag is for",
 *          format="string",
 *          example="Groups in Scotland"
 *     ),
 *     @OA\Property(
 *          property="network_id",
 *          title="network_id",
 *          description="Network this tag belongs to (null for global tags)",
 *          format="int64",
 *          example=1,
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="network_name",
 *          title="network_name",
 *          description="Name of the network this tag belongs to (null for global tags)",
 *          format="string",
 *          example="MRES",
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="groups_count",
 *          title="groups_count",
 *          description="Number of groups that have this tag applied",
 *          format="int64",
 *          example=5
 *     )
 * )
 */

class Tag extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->tag_name,
            'description' => $this->description,
            'network_id' => $this->network_id,
            'network_name' => $this->network ? $this->network->name : null,
            'groups_count' => $this->groupTagGroups()->count(),
        ];
    }
}

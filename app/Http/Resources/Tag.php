<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Tag",
 *     schema="Tag",
 *     description="A tag to record something about a group.",
 *     @OA\Xml(
 *         name="Network"
 *     ),
 * )
 */

class Tag extends JsonResource
{
    /**
     *     @OA\Property(
     *          property="id",
     *          title="id",
     *          description="Unique identifier of this tag",
     *          format="int64",
     *          example=1
     *     )
     */

    /**
     *     @OA\Property(
     *          property="name",
     *          title="name",
     *          description="Unique name of this tag",
     *          format="string",
     *          example="Scotland"
     *     )
     */

    /**
     *     @OA\Property(
     *          property="description",
     *          title="description",
     *          description="What this tag is for",
     *          format="string",
     *          example="Groups in Scotland"
     *     )
     */

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->tag_name,
            'description' => $this->description,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Skill",
 *     schema="Skill",
 *     description="A skill possessed by a volunteer.",
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this skill",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *          property="name",
 *          title="name",
 *          description="Name of this skill",
 *          format="string",
 *          example="First aid"
 *     ),
 *     @OA\Property(
 *          property="description",
 *          title="description",
 *          description="Optional description of this skill",
 *          format="string",
 *          example="This is for qualified First Aiders to identify themselves to event organisers"
 *     ),
 *     @OA\Property(
 *          property="category",
 *          title="category",
 *          description="Category of this skill",
 *          format="int64",
 *          example=1
 *     ),
 * )
 */

class Skill extends JsonResource
{
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
            'name' => $this->skill_name,
            'description' => $this->description,
            'category' => $this->category,
        ];
    }
}

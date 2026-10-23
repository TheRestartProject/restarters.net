<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
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
 *          property="skill_name",
 *          title="skill_name",
 *          description="Name of this skill",
 *          format="string",
 *          example="First aid"
 *     ),
 *     @OA\Property(
 *          property="description",
 *          title="description",
 *          description="Optional description of this skill",
 *          format="string",
 *          example="This is for qualified First Aiders to identify themselves to event organisers",
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="category",
 *          title="category",
 *          description="Category of this skill (1 = Organising, 2 = Technical; see Fixometer::skillCategories())",
 *          format="int64",
 *          example=1,
 *          nullable=true
 *     ),
 * )
 */

class Skill extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'skill_name' => $this->skill_name,
            'description' => $this->description,
            'category' => $this->category !== null ? (int) $this->category : null,
        ];
    }
}

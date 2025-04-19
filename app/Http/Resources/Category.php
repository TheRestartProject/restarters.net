<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Category",
 *     schema="Category",
 *     description="A class of items.",
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this category",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *          property="name",
 *          title="name",
 *          description="Unique name of this category",
 *          format="string",
 *          example="Scotland"
 *     ),
 *     @OA\Property(
 *          property="powered",
 *          title="powered",
 *          description="Whether the item is powered (true) or unpowered (false)",
 *          format="boolean",
 *          example="true"
 *     )
 * )
 */

class Category extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->idcategories,
            'name' => $this->name,
            'powered' => $this->powered,
        ];
    }
}

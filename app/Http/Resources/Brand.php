<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Brand",
 *     schema="Brand",
 *     description="A device brand (used to classify devices brought to repair events).",
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this brand",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *          property="brand_name",
 *          title="brand_name",
 *          description="Name of this brand",
 *          format="string",
 *          example="Sony"
 *     )
 * )
 */
class Brand extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_name' => $this->brand_name,
        ];
    }
}

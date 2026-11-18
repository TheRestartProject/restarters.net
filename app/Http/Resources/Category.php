<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Category",
 *     schema="Category",
 *     description="A class of items brought to repair events.",
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this category",
 *          format="int64",
 *          example=11
 *     ),
 *     @OA\Property(
 *          property="name",
 *          title="name",
 *          description="Unique name of this category",
 *          format="string",
 *          example="Desktop computer"
 *     ),
 *     @OA\Property(
 *          property="powered",
 *          title="powered",
 *          description="Whether the item is powered (true) or unpowered (false)",
 *          format="boolean",
 *          example=true,
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="weight",
 *          description="Average weight of an item in this category, in kg",
 *          type="number",
 *          format="float",
 *          example=8.5,
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="footprint",
 *          description="CO2 footprint per item, in kg",
 *          type="number",
 *          format="float",
 *          example=210.0,
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="footprint_reliability",
 *          description="Reliability of the footprint figure: 1 = Very poor ... 5 = Very good, 6 = N/A",
 *          type="integer",
 *          example=4,
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="cluster",
 *          description="ID of the cluster (parent grouping) this category belongs to",
 *          type="integer",
 *          example=1,
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="cluster_name",
 *          description="Display name of the cluster (joined for convenience)",
 *          type="string",
 *          example="Computers and Home Office",
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="description_short",
 *          description="Short admin description of the category",
 *          type="string",
 *          example="Desktop computers, all-in-ones, mini PCs",
 *          nullable=true
 *     )
 * )
 */
class Category extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idcategories,
            'name' => $this->name,
            'powered' => $this->powered !== null ? (bool) $this->powered : null,
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'footprint' => $this->footprint !== null ? (float) $this->footprint : null,
            'footprint_reliability' => $this->footprint_reliability !== null ? (int) $this->footprint_reliability : null,
            'cluster' => $this->cluster !== null ? (int) $this->cluster : null,
            'cluster_name' => $this->cluster_name ?? null,
            'description_short' => $this->description_short,
        ];
    }
}

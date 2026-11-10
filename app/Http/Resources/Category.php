<?php

namespace App\Http\Resources;

use App\Services\ItemImpact;

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
 *          property="typical_weight",
 *          description="The weight used for an item in this category when nobody enters one, in kg. For Misc it is the median of Misc weights entered in the last 12 months.",
 *          type="number",
 *          format="float",
 *          example=1.28,
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="weight_min",
 *          description="The lowest entered weight that counts towards impact figures, in kg",
 *          type="number",
 *          format="float",
 *          example=0.0128,
 *          nullable=true
 *     ),
 *     @OA\Property(
 *          property="weight_max",
 *          description="The highest entered weight that counts towards impact figures, in kg. Null when there is no limit.",
 *          type="number",
 *          format="float",
 *          example=12.8,
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
        ] + $this->weightsThatCount();
    }

    /**
     * The weight used when nobody enters one, and the range of entered weights that count towards impact
     * figures (see ItemImpact). weight_max is null when there is no upper limit.
     */
    private function weightsThatCount(): array
    {
        if ($this->weight === null || $this->powered === null) {
            return ['typical_weight' => null, 'weight_min' => null, 'weight_max' => null];
        }

        [$min, $max] = ItemImpact::countedRange((float) $this->weight, (bool) $this->powered);

        return [
            'typical_weight' => ItemImpact::typicalWeight((float) $this->weight, (bool) $this->powered),
            'weight_min' => $min,
            'weight_max' => $max,
        ];
    }
}

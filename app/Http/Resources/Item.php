<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Item",
 *     schema="Item",
 *     description="An item which can be specified in a Device",
 *     @OA\Property(
 *          property="type",
 *          title="type",
 *          description="The name of the item",
 *          format="string",
 *          example="Laptop"
 *     ),
 *     @OA\Property(
 *          property="powered",
 *          title="powered",
 *          description="Whether the item is powered (true) or unpowered (false)",
 *          format="boolean",
 *          example="true"
 *     ),
 *     @OA\Property(
 *          property="idcategories",
 *          title="idcategories",
 *          description="The id of the category for this item",
 *          format="int64",
 *          example="16"
 *     ),
 *     @OA\Property(
 *          property="categoryname",
 *          title="categoryname",
 *          description="The name of the category for this item",
 *          format="string",
 *          example="Laptop medium"
 *     )
 * )
 */

class Item extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->item_type,
            'powered' => $this->powered ? true : false,
            'idcategories' => intval($this->idcategories),
            'categoryname' => $this->categoryname,
        ];
    }
}

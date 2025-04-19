<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Image",
 *     schema="Image",
 *     description="An image of a Device",
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="The unique id of this image",
 *          format="number",
 *          example="1"
 *     ),
 *     @OA\Property(
 *          property="path",
 *          title="path",
 *          description="The path to the image",
 *          format="string",
 *          example="true"
 *     ),
 * )
 */

class Image extends JsonResource
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
            'id' => $this->idimages,
            'idxref' => $this->idxref,
            'path' => $this->path
        ];
    }
}

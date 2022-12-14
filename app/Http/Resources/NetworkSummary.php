<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="NetworkSummary",
 *     schema="NetworkSummary",
 *     description="The summary information about a network.  For the full information, fetch the network.",
 *     @OA\Xml(
 *         name="Network"
 *     ),
 * )
 */

class NetworkSummary extends JsonResource
{
    /**
     *     @OA\Property(
     *          property="id",
     *          title="id",
     *          description="Unique identifier of this network",
     *          format="int64",
     *          example=1
     *     )
     */

    /**
     *     @OA\Property(
     *          property="name",
     *          title="name",
     *          description="Unique name of this network",
     *          format="string",
     *          example="Default Network"
     *     )
     */

    /**
     *     @OA\Property(
     *          property="logo",
     *          title="image",
     *          description="URL of a logo for this network.  You should prefix this with /uploads before use.",
     *          format="string",
     *          example="/mid_1597853610178a4b76e4d666b2a7b32ee75d8a24c706f1cbf213970.png"
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
            'name' => $this->name,
            'logo' => $this->logo ? ($request->root() . '/uploads/network_logos/' . $this->logo) : null,
        ];
    }
}

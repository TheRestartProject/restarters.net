<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="GroupLocation",
 *     schema="GroupLocation",
 *     description="The information about the location of a group.",
 *     @OA\Xml(
 *         name="GroupLocation"
 *     ),
 * )
 */

class GroupLocation extends JsonResource
{
    /**
     *     @OA\Property(
     *          property="area",
     *          description="The free-form area that this group is in.",
     *          format="string",
     *          example="London"
     *     )
     */

    /**
     *     @OA\Property(
     *          property="location",
     *          description="The location that this group is in.  Must be geocodable.",
     *          format="string",
     *          example="College Road, London NW10 5EX, UK"
     *     )
     */

    /**
     *     @OA\Property(
     *          property="country",
     *          description="The free-form country.",
     *          format="string",
     *          example="United Kingdom"
     *     )
     */

    /**
     *     @OA\Property(
     *          property="lat",
     *          title="lat",
     *          description="Latitude of the group.",
     *          format="float",
     *          example="50.8113243"
     *     )
     */

    /**
     *     @OA\Property(
     *          property="lng",
     *          title="lng",
     *          description="Longitude of the group.",
     *          format="float",
     *          example="-1.0788839"
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
        $ret = [
            'location' => $this->location,
            'area' => $this->area,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];

        return($ret);
    }
}

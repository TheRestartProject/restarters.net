<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="GroupSummaryCollection",
 *     schema="GroupSummaryCollection",
 *     description="A collection of group summaries",
 *     @OA\Xml(
 *         name="GroupSummaryCollection"
 *     ),
 * )
 */


class GroupSummaryCollection extends ResourceCollection
{
    /**
     *     @OA\Property(
     *          property="data",
     *          title="data",
     *          description="Data wrapper",
     *          type="array",
     *          @OA\Items(ref="#/components/schemas/GroupSummary")
     *     )
     */

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

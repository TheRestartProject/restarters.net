<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="NetworkSummaryCollection",
 *     schema="NetworkSummaryCollection",
 *     description="A collection of network summaries.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/NetworkSummary"
 *     )
 * )
*/

class NetworkSummaryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}

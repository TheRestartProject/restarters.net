<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="EventSummaryCollection",
 *     schema="EventSummaryCollection",
 *     description="A collection of event summaries.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/EventSummary"
 *     )
 * )
*/

class PartySummaryCollection extends ResourceCollection
{
}

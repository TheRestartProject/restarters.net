<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="EventSummaryCollection",
 *     schema="EventSummaryCollection",
 *     description="A collection of event summaries.",
 *     @OA\Xml(
 *         name="EventSummaryCollection"
 *     ),
 * )
 */

class PartySummaryCollection extends ResourceCollection
{
}

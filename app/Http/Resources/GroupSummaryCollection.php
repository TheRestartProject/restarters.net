<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="GroupSummaryCollection",
 *     schema="GroupSummaryCollection",
 *     description="A collection of group summaries.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/GroupSummary"
 *     )
 * )
*/

class GroupSummaryCollection extends ResourceCollection
{
}

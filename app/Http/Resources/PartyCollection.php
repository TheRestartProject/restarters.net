<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="EventCollection",
 *     schema="EventCollection",
 *     description="A collection of events.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/Event"
 *     )
 * * )
 */

class PartyCollection extends ResourceCollection
{
}

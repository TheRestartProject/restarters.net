<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="EventCollection",
 *     schema="EventCollection",
 *     description="A collection of events.",
 *     @OA\Xml(
 *         name="EventCollection"
 *     ),
 * )
 */

class PartyCollection extends ResourceCollection
{
}

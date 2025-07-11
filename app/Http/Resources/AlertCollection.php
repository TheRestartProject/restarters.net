<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     title="AlertCollection",
 *     schema="AlertCollection",
 *     description="A collection of Alerts.",
 *     type="array",
 *     @OA\Items(
 *         ref="#/components/schemas/Alert"
 *     )
 * )
 */

class AlertCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}

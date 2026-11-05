<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Permission",
 *     schema="Permission",
 *     description="A permission that can be granted to a role.",
 *     @OA\Property(property="id", type="integer", format="int64", example=4),
 *     @OA\Property(property="name", type="string", example="Create Party")
 * )
 */
class Permission extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this['id'],
            'name' => $this['name'],
        ];
    }
}

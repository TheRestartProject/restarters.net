<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="RoleAdmin",
 *     schema="RoleAdmin",
 *     description="A user role and its permissions, for the admin matrix.",
 *     @OA\Property(property="id", type="integer", format="int64", example=3),
 *     @OA\Property(property="name", type="string", example="Host"),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         description="IDs of the permissions granted to this role",
 *         @OA\Items(type="integer", example=4)
 *     ),
 *     @OA\Property(
 *         property="permissions_list",
 *         type="string",
 *         description="Comma-separated list of permission names for display",
 *         example="Create Party, View Reports"
 *     )
 * )
 */
class RoleAdmin extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this['id'],
            'name' => $this['name'],
            'permissions' => array_map('intval', $this['permissions']),
            'permissions_list' => $this['permissions_list'] ?? '',
        ];
    }
}

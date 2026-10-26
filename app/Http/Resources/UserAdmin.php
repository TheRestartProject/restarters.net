<?php

namespace App\Http\Resources;

use App\Helpers\Fixometer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="UserAdmin",
 *     schema="UserAdmin",
 *     description="A user row for the admin user list",
 *     @OA\Property(property="id", type="integer", format="int64", example=42),
 *     @OA\Property(property="name", type="string", example="Jane Doe"),
 *     @OA\Property(property="email", type="string", example="jane@example.com"),
 *     @OA\Property(property="role", type="integer", example=3),
 *     @OA\Property(property="role_name", type="string", example="Host"),
 *     @OA\Property(property="location", type="string", nullable=true, example="London"),
 *     @OA\Property(property="country", type="string", nullable=true, example="GB"),
 *     @OA\Property(property="country_name", type="string", nullable=true, example="United Kingdom"),
 *     @OA\Property(property="groups_count", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true)
 * )
 */
class UserAdmin extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastLogin = method_exists($this->resource, 'lastLogin') ? $this->resource->lastLogin() : null;

        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => (int) $this->role,
            'role_name' => $this->role_name ?? (string) $this->getRoleName(),
            'location' => $this->location,
            'country' => $this->country_code,
            'country_name' => $this->country_code ? Fixometer::getCountryFromCountryCode($this->country_code) : null,
            'groups_count' => (int) ($this->groups_count ?? $this->groups()->count()),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'last_login_at' => optional($lastLogin)?->toIso8601String(),
        ];
    }

    private function getRoleName(): string
    {
        $roleNames = [
            \App\Role::ROOT => 'Root',
            \App\Role::ADMINISTRATOR => 'Administrator',
            \App\Role::NETWORK_COORDINATOR => 'NetworkCoordinator',
            \App\Role::HOST => 'Host',
            \App\Role::RESTARTER => 'Restarter',
        ];

        return $roleNames[$this->role] ?? '';
    }
}

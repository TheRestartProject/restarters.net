<?php

namespace Database\Factories;

use App\ApiClient;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiClientFactory extends Factory
{
    protected $model = ApiClient::class;

    public function definition()
    {
        $plainToken = 'token_'.$this->faker->unique()->lexify('????????????????');

        return [
            'name' => $this->faker->company(),
            'token_hash' => hash('sha256', $plainToken),
            'token_hint' => substr($plainToken, 0, 6).'...'.substr($plainToken, -4),
            'scopes' => ['events:read'],
            'allowed_origins' => null,
            'allowed_network_ids' => null,
            'rate_limit_per_minute' => 120,
            'active' => true,
            'expires_at' => null,
            'last_used_at' => null,
        ];
    }
}

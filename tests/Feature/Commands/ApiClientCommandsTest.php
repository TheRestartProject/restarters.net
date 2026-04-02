<?php

namespace Tests\Feature\Commands;

use App\ApiClient;
use App\Network;
use Tests\TestCase;

class ApiClientCommandsTest extends TestCase
{
    public function testCreateCommandCreatesApiClient()
    {
        $network = Network::factory()->create();

        $this->artisan('api-clients:create', [
            '--name' => 'CLI client',
            '--origins' => 'https://allowed.example,https://second.example',
            '--networks' => (string) $network->id,
            '--rate' => 99,
            '--expires-at' => '2030-01-01 10:00:00',
        ])->assertExitCode(0);

        $client = ApiClient::where('name', 'CLI client')->first();
        $this->assertNotNull($client);
        $this->assertEquals(['events:read'], $client->scopes);
        $this->assertEquals(['https://allowed.example', 'https://second.example'], $client->allowed_origins);
        $this->assertEquals([$network->id], $client->allowed_network_ids);
        $this->assertEquals(99, $client->rate_limit_per_minute);
        $this->assertTrue($client->active);
        $this->assertNotEmpty($client->token_hash);
        $this->assertNotEmpty($client->token_hint);
    }

    public function testRotateCommandChangesTokenAndReactivatesClient()
    {
        $client = ApiClient::factory()->create([
            'active' => false,
            'token_hash' => hash('sha256', 'before_rotate'),
            'token_hint' => 'before...1234',
        ]);

        $this->artisan('api-clients:rotate', [
            'id' => $client->id,
        ])->assertExitCode(0);

        $client->refresh();
        $this->assertTrue($client->active);
        $this->assertNotEquals(hash('sha256', 'before_rotate'), $client->token_hash);
        $this->assertNotEquals('before...1234', $client->token_hint);
    }

    public function testRevokeCommandDisablesClient()
    {
        $client = ApiClient::factory()->create([
            'active' => true,
        ]);

        $this->artisan('api-clients:revoke', [
            'id' => $client->id,
        ])->assertExitCode(0);

        $client->refresh();
        $this->assertFalse($client->active);
    }
}

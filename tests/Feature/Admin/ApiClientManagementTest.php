<?php

namespace Tests\Feature\Admin;

use App\ApiClient;
use App\Network;
use App\Role;
use Tests\TestCase;

class ApiClientManagementTest extends TestCase
{
    public function testAdministratorCanViewApiClientsPage()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->get('/admin/api-clients');
        $response->assertSuccessful();
        $response->assertSee('API clients');
    }

    public function testNonAdministratorCannotAccessApiClientsPage()
    {
        $this->loginAsTestUser(Role::RESTARTER);

        $response = $this->get('/admin/api-clients');
        $response->assertRedirect('/user/forbidden');
    }

    public function testAdministratorCanCreateApiClientFromAdminPage()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $network = Network::factory()->create(['name' => 'Allowed Network']);

        $response = $this->post('/admin/api-clients', [
            'name' => 'Display integration',
            'allowed_origins' => "https://allowed.example\nhttps://second.example",
            'allowed_network_ids' => [$network->id],
            'rate_limit_per_minute' => 77,
            'expires_at' => '2030-01-01T12:00',
        ]);

        $response->assertRedirect('/admin/api-clients');
        $response->assertSessionHas('generated_api_token');

        $client = ApiClient::where('name', 'Display integration')->first();
        $this->assertNotNull($client);
        $this->assertEquals(['events:read'], $client->scopes);
        $this->assertEquals(['https://allowed.example', 'https://second.example'], $client->allowed_origins);
        $this->assertEquals([$network->id], $client->allowed_network_ids);
        $this->assertEquals(77, $client->rate_limit_per_minute);
        $this->assertNotEmpty($client->token_hash);
        $this->assertNotEmpty($client->token_hint);
    }

    public function testAdministratorCanRotateApiClientFromAdminPage()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $client = ApiClient::factory()->create([
            'token_hash' => hash('sha256', 'before_rotate'),
            'token_hint' => 'before...1234',
            'active' => false,
        ]);

        $response = $this->post("/admin/api-clients/{$client->id}/rotate");
        $response->assertRedirect('/admin/api-clients');
        $response->assertSessionHas('generated_api_token');

        $client->refresh();
        $this->assertNotEquals(hash('sha256', 'before_rotate'), $client->token_hash);
        $this->assertNotEquals('before...1234', $client->token_hint);
        $this->assertTrue($client->active);
    }

    public function testAdministratorCanRevokeApiClientFromAdminPage()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $client = ApiClient::factory()->create([
            'active' => true,
        ]);

        $response = $this->post("/admin/api-clients/{$client->id}/revoke");
        $response->assertRedirect('/admin/api-clients');

        $client->refresh();
        $this->assertFalse($client->active);
    }
}

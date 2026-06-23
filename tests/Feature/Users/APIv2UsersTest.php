<?php

namespace Tests\Feature\Users;

use App\Role;
use App\User;
use Tests\TestCase;

class APIv2UsersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testListUsersRequiresAuth(): void
    {
        $response = $this->getJson('/api/v2/users');
        $response->assertStatus(401);
    }

    public function testListUsersForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/users?api_token=r1');
        $response->assertStatus(403);
    }

    public function testListUsersAsAdminReturnsPaginated(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        User::factory()->host()->create(['name' => 'Aaa Host']);
        User::factory()->restarter()->create(['name' => 'Bbb Restarter']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/users?api_token=admin1');
        $response->assertSuccessful();

        $payload = $response->json();
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('current_page', $payload['meta']);
        $this->assertArrayHasKey('total', $payload['meta']);
        $this->assertIsArray($payload['data']);

        foreach ($payload['data'] as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('email', $row);
            $this->assertArrayHasKey('role', $row);
            $this->assertArrayHasKey('role_name', $row);
            $this->assertArrayHasKey('location', $row);
            $this->assertArrayHasKey('country', $row);
            $this->assertArrayHasKey('country_name', $row);
            $this->assertArrayHasKey('groups_count', $row);
            $this->assertArrayHasKey('created_at', $row);
            $this->assertArrayHasKey('last_login_at', $row);
        }
    }

    public function testListUsersFilterByName(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        User::factory()->host()->create(['name' => 'Alpha Person']);
        User::factory()->host()->create(['name' => 'Bravo Person']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/users?api_token=admin1&name=Alpha');
        $response->assertSuccessful();

        $names = array_column($response->json('data'), 'name');
        $this->assertContains('Alpha Person', $names);
        $this->assertNotContains('Bravo Person', $names);
    }

    public function testListUsersFilterByEmail(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        User::factory()->host()->create(['email' => 'unique-target@example.com']);
        User::factory()->host()->create(['email' => 'noise@example.com']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/users?api_token=admin1&email=unique-target');
        $response->assertSuccessful();

        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('unique-target@example.com', $emails);
        $this->assertNotContains('noise@example.com', $emails);
    }

    public function testListUsersFilterByRole(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $host = User::factory()->host()->create();
        User::factory()->restarter()->create();
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/users?api_token=admin1&role=' . Role::HOST);
        $response->assertSuccessful();

        $roles = array_unique(array_column($response->json('data'), 'role'));
        $this->assertEquals([Role::HOST], array_values($roles));
    }

    public function testListUsersSortByNameAsc(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        User::factory()->host()->create(['name' => 'Zeta Sortable']);
        User::factory()->host()->create(['name' => 'Alpha Sortable']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/users?api_token=admin1&name=Sortable&sort=name&sortdir=asc');
        $response->assertSuccessful();

        $names = array_column($response->json('data'), 'name');
        $alphaIdx = array_search('Alpha Sortable', $names);
        $zetaIdx = array_search('Zeta Sortable', $names);
        $this->assertNotFalse($alphaIdx);
        $this->assertNotFalse($zetaIdx);
        $this->assertLessThan($zetaIdx, $alphaIdx);
    }
}

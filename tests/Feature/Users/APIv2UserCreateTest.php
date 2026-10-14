<?php

namespace Tests\Feature\Users;

use App\Role;
use App\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class APIv2UserCreateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testCreateRequiresAuth(): void
    {
        $response = $this->postJson('/api/v2/users', [
            'name' => 'New Person',
            'email' => 'new-person-noauth@example.com',
            'role' => Role::RESTARTER,
            'password' => 'password123',
        ]);
        $response->assertStatus(401);
    }

    public function testCreateForbiddenForNonAdmin(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($host);

        $response = $this->postJson('/api/v2/users?api_token=tok1', [
            'name' => 'New Person',
            'email' => 'new-person-forbidden@example.com',
            'role' => Role::RESTARTER,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $this->assertNull(User::where('email', 'new-person-forbidden@example.com')->first());
    }

    public function testAdminCanCreateUser(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/users?api_token=tok1', [
            'name' => 'New Person',
            'email' => 'new-person-created@example.com',
            'role' => Role::HOST,
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('New Person', $response->json('data.name'));
        $this->assertEquals('new-person-created@example.com', $response->json('data.email'));
        $this->assertEquals(Role::HOST, $response->json('data.role'));

        $created = User::where('email', 'new-person-created@example.com')->first();
        $this->assertNotNull($created);
        $this->assertEquals(Role::HOST, $created->role);
        $this->assertTrue(Hash::check('password123', $created->password));
        $this->assertNotEmpty($created->username);
    }

    public function testCreateResponseNeverContainsPassword(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/users?api_token=tok1', [
            'name' => 'New Person',
            'email' => 'new-person-pii@example.com',
            'role' => Role::RESTARTER,
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        $json = $response->getContent();
        $this->assertStringNotContainsString('password123', $json);
        $this->assertStringNotContainsString('"password"', $json);
    }

    public function testCreateDuplicateEmailReturns422(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $this->actingAs($admin);
        User::factory()->restarter()->create(['email' => 'existing-create-test@example.com']);

        $response = $this->postJson('/api/v2/users?api_token=tok1', [
            'name' => 'New Person',
            'email' => 'existing-create-test@example.com',
            'role' => Role::RESTARTER,
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function testCreateMissingPasswordReturns422(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/users?api_token=tok1', [
            'name' => 'New Person',
            'email' => 'new-person-nopass@example.com',
            'role' => Role::RESTARTER,
        ]);

        $response->assertStatus(422);
        $this->assertNull(User::where('email', 'new-person-nopass@example.com')->first());
    }

    public function testCreateShortPasswordReturns422(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/users?api_token=tok1', [
            'name' => 'New Person',
            'email' => 'new-person-shortpass@example.com',
            'role' => Role::RESTARTER,
            'password' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function testCreateInvalidRoleReturns422(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/users?api_token=tok1', [
            'name' => 'New Person',
            'email' => 'new-person-badrole@example.com',
            'role' => 999999,
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function testCreateMissingNameReturns422(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/users?api_token=tok1', [
            'email' => 'new-person-noname@example.com',
            'role' => Role::RESTARTER,
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }
}

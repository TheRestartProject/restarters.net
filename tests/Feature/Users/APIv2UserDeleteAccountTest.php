<?php

namespace Tests\Feature\Users;

use App\User;
use Tests\TestCase;

class APIv2UserDeleteAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testRequiresAuth(): void
    {
        $response = $this->deleteJson('/api/v2/users/me');
        $response->assertStatus(401);
    }

    public function testSoftDeletesCallingUser(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'del-acct-tok-1']);
        $id = $user->id;
        $this->actingAs($user);

        $response = $this->deleteJson('/api/v2/users/me?api_token=del-acct-tok-1');

        $response->assertSuccessful();
        $response->assertJson(['data' => ['success' => true]]);

        // Soft-deleted: gone from normal queries, still present (and trashed) via withTrashed.
        $this->assertNull(User::find($id));

        $trashed = User::withTrashed()->find($id);
        $this->assertNotNull($trashed);
        $this->assertTrue($trashed->trashed());
    }

    public function testCannotDeleteAnotherUsersAccount(): void
    {
        // The endpoint always operates on Auth::user() - there is no id parameter, so confirm
        // that smuggling another user's id in the request body has no effect on who gets deleted.
        $attacker = User::factory()->restarter()->create(['api_token' => 'del-acct-tok-2']);
        $victim = User::factory()->restarter()->create(['api_token' => 'del-acct-tok-3']);
        $this->actingAs($attacker);

        $response = $this->deleteJson('/api/v2/users/me?api_token=del-acct-tok-2', ['id' => $victim->id]);

        $response->assertSuccessful();

        // Attacker deleted themselves; victim untouched.
        $this->assertNull(User::find($attacker->id));
        $this->assertNotNull(User::find($victim->id));
    }
}

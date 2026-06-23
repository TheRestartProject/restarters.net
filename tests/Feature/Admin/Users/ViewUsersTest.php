<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class ViewUsersTest extends TestCase
{
    public function testAdminCanLoadUsersPage(): void
    {
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->get('/user/all');
        $response->assertSuccessful();
        $response->assertSee('<UsersPage', false);
    }

    public function testNonAdminCannotLoadUsersPage(): void
    {
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $response = $this->get('/user/all');
        $response->assertRedirect('/user/forbidden');
    }
}

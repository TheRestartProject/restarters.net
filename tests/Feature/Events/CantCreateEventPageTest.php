<?php

namespace Tests\Feature\Events;

use App\Role;
use App\User;
use Tests\TestCase;

class CantCreateEventPageTest extends TestCase
{
    public function testRestarterSeesVueCantCreatePage(): void
    {
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $response = $this->get('/party/create');
        $response->assertSuccessful();
        $response->assertSee('<CantCreateEventPage', false);
    }
}

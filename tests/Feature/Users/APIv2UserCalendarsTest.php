<?php

namespace Tests\Feature\Users;

use App\Group;
use App\User;
use Tests\TestCase;

class APIv2UserCalendarsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testCalendarsRequiresAuth(): void
    {
        $response = $this->getJson('/api/v2/users/me/calendars');
        $response->assertStatus(401);
    }

    public function testCalendarsReturnsUserUrlForHost(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'calendar_hash' => 'cal-host-1',
        ]);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/users/me/calendars?api_token=tok1');
        $response->assertSuccessful();
        $data = $response->json('data');

        $this->assertStringContainsString('/calendar/user/cal-host-1', $data['user_url']);
        $this->assertIsArray($data['groups']);
        $this->assertIsArray($data['group_areas']);
        $this->assertFalse($data['is_admin']);
        $this->assertNull($data['admin_all_events_url']);
    }

    public function testCalendarsAdminFlagAndAllEventsUrl(): void
    {
        $admin = User::factory()->administrator()->create([
            'api_token' => 'admin1',
            'calendar_hash' => 'cal-admin-1',
        ]);
        $this->actingAs($admin);

        config(['app.url' => 'http://localhost']);
        // Ensure CALENDAR_HASH is set so the admin URL surfaces.
        putenv('CALENDAR_HASH=test-cal-hash');

        $response = $this->getJson('/api/v2/users/me/calendars?api_token=admin1');
        $response->assertSuccessful();
        $data = $response->json('data');

        $this->assertTrue($data['is_admin']);
        if (env('CALENDAR_HASH')) {
            $this->assertNotNull($data['admin_all_events_url']);
            $this->assertStringContainsString('test-cal-hash', $data['admin_all_events_url']);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Registration must fire UserRegistered, which drives the Discourse account
 * creation listeners (AddUserToDiscourseGroup / DiscourseUserEventSubscriber).
 * Registration now happens via POST /api/v2/auth/register (registerv2); the
 * broader registration behaviour is covered by AuthEndpointsTest, but the
 * event-dispatch guarantee is pinned here.
 */
class DiscourseAccountCreationTest extends TestCase
{
    /** @test */
    public function user_registration_triggers_user_registered_event(): void
    {
        Event::fake([UserRegistered::class]);

        $response = $this->post('/api/v2/auth/register', [
            'name' => 'New Volunteer',
            'email' => 'discourse-newbie@restarters.test',
            'password' => 'longenough',
            'password_confirmation' => 'longenough',
            'age' => '1980',
            'country' => 'GBR',
            'city' => 'London',
            'consent_gdpr' => true,
            'consent_future_data' => true,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);

        $user = User::where('email', 'discourse-newbie@restarters.test')->first();
        $this->assertNotNull($user);

        Event::assertDispatched(UserRegistered::class);
    }
}

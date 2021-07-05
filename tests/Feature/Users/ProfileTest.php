<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\User;

use DB;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileTest extends TestCase
{
    public function testProfilePage() {
        $user = factory(User::class)->states('Restarter')->create();

        // When logged out should throw an exception.
        try {
            $response = $this->get('/profile');
            $this->assertFalse(TRUE);
        } catch (AuthenticationException $e) {
            // Success case.
        }

        // When logged in should be able to see.
        // TODO I'm not convinced that viewing /profile is ever reachable, though /profile/id is.
        $this->actingAs($user);

        $response = $this->get('/profile');
        $response->assertSee(__('profile.my_skills'));

        // ...and also by id.
        $response = $this->get('/profile/' . $user->id);
        $response->assertSee(__('profile.my_skills'));
    }
}

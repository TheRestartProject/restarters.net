<?php

namespace Tests\Feature;

use App\Policies\UserPolicy;
use App\Role;
use App\User;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    public function testSoftDelete(): void {
        // POST /user/soft-delete is dead (Nuxt cutover); there is no v2 API endpoint for an
        // admin soft-deleting ANOTHER user - deleteMyAccountv2 always operates on
        // Auth::user() only (see APIv2UserDeleteAccountTest::testSoftDeletesCallingUser /
        // testCannotDeleteAnotherUsersAccount, which cover the self-delete + anonymization
        // path). The authorization rule this route relied on (self or administrator) is
        // still live in UserPolicy::delete(), so assert against that directly, and confirm
        // the soft-delete it would have triggered.
        $user = User::factory()->restarter()->create();
        $admin = $this->loginAsTestUser(Role::ADMINISTRATOR);

        $policy = new UserPolicy();

        // An administrator acting on another user - allowed.
        $this->assertTrue($policy->delete($admin, $user));
        $user->delete();
        $this->assertTrue($user->fresh()->trashed());

        // A non-admin, non-self actor - forbidden.
        $other = User::factory()->restarter()->create();
        $victim = User::factory()->restarter()->create();
        $this->assertFalse($policy->delete($other, $victim));
    }
}

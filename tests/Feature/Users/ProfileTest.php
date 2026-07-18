<?php

namespace Tests\Feature;

use App\Policies\UserPolicy;
use App\User;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function testEdit(): void
    {
        // POST /user/edit/{id} is dead (Nuxt cutover); there is no v2 API equivalent for an
        // admin editing ANOTHER user's basic profile fields (name/email) - updateMyProfilev2
        // only ever operates on Auth::user() (see APIv2UserProfileTest). The permission matrix
        // itself (self yes, admin yes, everyone else no - including the F002 account-takeover
        // fix restricting hosts) is still live logic in UserPolicy::update(), so assert
        // against that directly instead of going through the removed route.
        $user1 = User::factory()->restarter()->create();
        $user2 = User::factory()->restarter()->create();
        $host = User::factory()->host()->create();
        $nc = User::factory()->networkCoordinator()->create();
        $admin = User::factory()->administrator()->create();

        $policy = new UserPolicy();

        // As yourself - allowed.
        $this->assertTrue($policy->update($user1, $user1));

        // A restarter acting on another restarter - forbidden.
        $this->assertFalse($policy->update($user2, $user1));

        // A host acting on an unrelated restarter - forbidden (F002 account-takeover fix).
        $this->assertFalse($policy->update($host, $user1));

        // A network coordinator acting on a restarter - forbidden.
        $this->assertFalse($policy->update($nc, $user1));

        // An administrator acting on a restarter - allowed.
        $this->assertTrue($policy->update($admin, $user1));
    }

    public function testAPI(): void {
        $user = User::factory()->administrator()->create([
                                                                      'api_token' => '1234',
                                                                  ]);
        $this->actingAs($user);
        $response = $this->get('/api/users/me?api_token=1234');
        $response->assertSuccessful();
        $ret = json_decode($response->getContent(), TRUE);
        self::assertEquals($user->id, $ret['id']);
        self::assertEquals($user->email, $ret['email']);
        self::assertEquals($user->name, $ret['name']);

        $response = $this->get('/api/users');
        $response->assertSuccessful();
        $ret = json_decode($response->getContent(), TRUE);
        self::assertEquals(1, count($ret));
        self::assertEquals($user->id, $ret[0]['id']);
    }
}

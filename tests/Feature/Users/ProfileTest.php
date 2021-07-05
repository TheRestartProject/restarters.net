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

    /**
     * @runInSeparateProcess
     */
    public function testEdit() {
        $user1 = factory(User::class)->states('Restarter')->create();
        $user2 = factory(User::class)->states('Restarter')->create();
        $host = factory(User::class)->states('Host')->create();
        $nc = factory(User::class)->states('NetworkCoordinator')->create();
        $admin = factory(User::class)->states('Administrator')->create();

        $editdata = [
            'id' => $user1->id,
            'name' => 'Test',
            'groups' => [],
            'email' => 'test@bloggs.net'
        ];

        // When logged out should throw an exception.
        try {
            $response = $this->post('/user/edit/' . $user1->id, $editdata);
            $this->assertFalse(TRUE);
        } catch (AuthenticationException $e) {
            // Success case.
        }

        // TODO These are the behaviours as coded, but they seem weird.
        //
        // As yourself.
        $this->actingAs($user1);

        $response = $this->post('/user/edit/' . $user1->id, $editdata);

        $this->assertEquals('', $response->getContent());

        // A restart acting on another restart.
        $this->actingAs($user2);

        $response = $this->post('/user/edit/' . $user1->id, $editdata);

        $this->assertEquals('', $response->getContent());

        // A host acting on a restarter - can.
        $this->actingAs($host);

        $response = $this->post('/user/edit/' . $user1->id, $editdata);

        $response->assertSee('Edit User');

        // A network coordinator acting on a restarter - can't.
        $this->actingAs($nc);

        $response = $this->post('/user/edit/' . $user1->id, $editdata);

        $response->assertSee('');

        // An administrator acting on a restarter - can.
        $this->actingAs($host);

        $response = $this->post('/user/edit/' . $user1->id, $editdata);

        $response->assertSee('Edit User');
    }

    /**
     * @runInSeparateProcess
     */
    public function testEditBadPassword() {
        $user1 = factory(User::class)->states('Restarter')->create();
        $host = factory(User::class)->states('Host')->create();

        $editdata = [
            'id' => $user1->id,
            'name' => 'Test',
            'groups' => [],
            'email' => 'test@bloggs.net',
            'new-password' => 'test1',
            'password-confirm' => 'test2',
        ];

        $this->actingAs($host);
        $response = $this->post('/user/edit/' . $user1->id, $editdata);

        $response->assertSee('The passwords are not identical!');
    }
}

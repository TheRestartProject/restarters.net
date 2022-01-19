<?php

namespace Tests\Feature;

use App\Role;
use App\User;
use DB;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class ProfileTest extends TestCase
{
    public function testProfilePage()
    {
        $user = factory(User::class)->states('Restarter')->create();

        // When logged out should throw an exception.
        try {
            $response = $this->get('/profile');
            $this->assertFalse(true);
        } catch (AuthenticationException $e) {
            // Success case.
        }

        // When logged in should be able to see.
        // TODO I'm not convinced that viewing /profile is ever reachable, though /profile/id is.
        $this->actingAs($user);

        $response = $this->get('/profile');
        $response->assertSee(__('profile.my_skills'));

        // ...and also by id.
        $response = $this->get('/profile/'.$user->id);
        $response->assertSee(__('profile.my_skills'));
    }

    public function testEdit()
    {
        $GLOBALS['_FILES'] = [];
        $user1 = factory(User::class)->states('Restarter')->create();
        $user2 = factory(User::class)->states('Restarter')->create();
        $host = factory(User::class)->states('Host')->create();
        $nc = factory(User::class)->states('NetworkCoordinator')->create();
        $admin = factory(User::class)->states('Administrator')->create();

        $editdata = [
            'id' => $user1->id,
            'name' => 'Test',
            'groups' => [],
            'email' => 'test@bloggs.net',
        ];

        // When logged out should throw an exception.
        try {
            $response = $this->post('/user/edit/'.$user1->id, $editdata);
            $this->assertFalse(true);
        } catch (AuthenticationException $e) {
            // Success case.
        }

        // TODO These are the behaviours as coded, but they seem weird.
        //
        // As yourself.
        $this->actingAs($user1);

        $response = $this->post('/user/edit/'.$user1->id, $editdata);

        $this->assertEquals('', $response->getContent());

        // A restart acting on another restart.
        $this->actingAs($user2);

        $response = $this->post('/user/edit/'.$user1->id, $editdata);

        $this->assertEquals('', $response->getContent());

        // A host acting on a restarter - can.
        $this->actingAs($host);

        $response = $this->post('/user/edit/'.$user1->id, $editdata);

        $response->assertSee('Edit User');

        // A network coordinator acting on a restarter - can't.
        $this->actingAs($nc);

        $response = $this->post('/user/edit/'.$user1->id, $editdata);

        $response->assertSee('');

        // An administrator acting on a restarter - can.
        $this->actingAs($host);

        $response = $this->post('/user/edit/'.$user1->id, $editdata);

        $response->assertSee('Edit User');
    }

    public function testEditBadPassword()
    {
        $GLOBALS['_FILES'] = [];
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
        $response = $this->post('/user/edit/'.$user1->id, $editdata);

        $response->assertSee('The passwords are not identical!');
    }

    public function testBadMediaWikiId()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/thumbnail?wiki_username=invalid');
    }

    public function testChangePassword() {
        $user = factory(User::class)->states('Restarter')->create();
        $user->setPassword(Hash::make('secret1'));

        $this->actingAs($user);

        $response = $this->post('/profile/edit-password', ['current-password' => 'secret', 'new-password' => 'f00', 'new-password-repeat' => 'f00']);
        $this->assertTrue($response->isRedirection());
        $this->assertEquals(__('profile.password_old_mismatch'), \Session::get('error'));

        $response = $this->post('/profile/edit-password', ['current-password' => 'secret', 'new-password' => 'f00', 'new-password-repeat' => 'f01']);
        $this->assertTrue($response->isRedirection());
        $this->assertEquals(__('profile.password_new_mismatch'), \Session::get('error'));

        $response = $this->post('/profile/edit-password', ['current-password' => 'secret1', 'new-password' => 'f00', 'new-password-repeat' => 'f00']);
        $this->assertTrue($response->isRedirection());
        $this->assertEquals(__('profile.password_changed'), \Session::get('message'));
    }

    public function testRepairDirectoryRole() {
        $user = factory(User::class)->states('Restarter')->create();
        $admin = factory(User::class)->states('Administrator')->create([
            'repairdir_role' => Role::REPAIR_DIRECTORY_SUPERADMIN
        ]);

        $this->actingAs($admin);

        $response = $this->post('/profile/edit-repair-directory', [
            'id' => $user->id,
            'role' => Role::REPAIR_DIRECTORY_EDITOR
        ]);
        $this->assertTrue($response->isRedirection());
        $this->assertEquals(__('profile.profile_updated'), \Session::get('message'));
    }

    public function testLanguage() {
        $user = factory(User::class)->states('Restarter')->create();
        $this->actingAs($user);

        $response = $this->post('/profile/edit-language', [
            'id' => $user->id,
            'user_language' => 'fr'
        ]);
        $this->assertTrue($response->isRedirection());
        $this->assertEquals(__('profile.language_updated'), \Session::get('message'));
    }
}
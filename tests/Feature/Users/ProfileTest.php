<?php

namespace Tests\Feature;

use App\Models\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\User;
use DB;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class ProfileTest extends TestCase
{
    public function testProfilePage(): void
    {
        $user = User::factory()->restarter()->create();

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

    public function testEdit(): void
    {
        $GLOBALS['_FILES'] = [];
        $user1 = User::factory()->restarter()->create();
        $user2 = User::factory()->restarter()->create();
        $host = User::factory()->host()->create();
        $nc = User::factory()->networkCoordinator()->create();
        $admin = User::factory()->administrator()->create();

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

    public function testEditBadPassword(): void
    {
        $GLOBALS['_FILES'] = [];
        $user1 = User::factory()->restarter()->create();
        $host = User::factory()->host()->create();

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

    public function testBadMediaWikiId(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/thumbnail?wiki_username=invalid');
    }

    public function testChangePassword(): void {
        $user = User::factory()->restarter()->create();
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

    public function testRepairDirectoryRole(): void {
        $user = User::factory()->restarter()->create();
        $admin = User::factory()->administrator()->create([
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

    public function testLanguage(): void {
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $response = $this->post('/profile/edit-language', [
            'id' => $user->id,
            'user_language' => 'fr'
        ]);
        $this->assertTrue($response->isRedirection());
        $this->assertEquals(__('profile.language_updated'), \Session::get('message'));
    }

    #[DataProvider('invitesProvider')]
    public function testInvites($admin, $invites): void {
        $user = User::factory()->restarter()->create();

        $params = [];

        if ($invites) {
            $params['invites'] = $invites;
        }

        if ($admin) {
            $this->loginAsTestUser(Role::ADMINISTRATOR);
            $params['id'] = $user->id;
        } else {
            $this->actingAs($user);
        }

        $response = $this->post('/profile/edit-preferences', $params);
        $this->assertTrue($response->isRedirection());
        $this->assertEquals(__('profile.preferences_updated'), \Session::get('message'));

        $user->refresh();
        $this->assertEquals($invites, $user->invites);
    }

    public function invitesProvider(): array {
        return [
            [ FALSE, 0 ],
            [ FALSE, 1 ],
            [ TRUE, 0 ],
            [ TRUE, 1 ],
        ];
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
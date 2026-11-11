<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\Network;
use App\User;
use App\UsersPermissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class MenusTest extends TestCase
{
    public function provider()
    {
        return [
            [
                'Administrator',
                [
                    0 => 'Administrator',
                    1 => 'General',
                ],
                true,
                [
                    0 => 'Brands',
                    1 => 'Skills',
                    2 => 'Group tags',
                    3 => 'Categories',
                    4 => 'Users',
                    5 => 'Roles',
                    6 => 'Networks',
                    7 => 'Translations',
                ]
            ],
            [
                'NetworkCoordinator',
                [
                    0 => 'Administrator',
                    1 => 'General',
                ],
                true,
                [
                    0 => 'Translations',
                    1 => 'Networks'
                ]
            ],
            [
                'Host',
                [
                    0 => 'General',
                ],
                false,
                []
            ],
            [
                'Restarter',
                [
                    0 => 'General',
                ],
                false,
                []
            ],
        ];
    }

    /**
     *@dataProvider provider
     */
    public function testSections($role, $present, $translator, $adminMenu): void
    {
        $user = User::factory()->{lcfirst($role)}()->create();

        if ($translator) {
            $up = new UsersPermissions();
            $up->user_id = $user->id;
            $up->permission_id = 6;
            $up->save();
        }

        $user->mediawiki = 'Wiki-'.$user->id;
        $user->save();

        // The wiki's server asks, so nobody is logged in.
        Auth::logout();
        session()->flush();

        $response = $this->get('/user/menus?wiki_username='.urlencode($user->mediawiki));
        $menus = json_decode($response->getContent(), true);

        $this->assertEquals($present, array_keys($menus));

        if ($adminMenu && count($adminMenu)) {
            $this->assertEquals($adminMenu, array_keys($menus['Administrator']['items']));
        }
    }

    public function testUnknownWikiUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/menus?wiki_username=Nobody');
    }

    public function testNoWikiUserNamed(): void
    {
        // Without a name the lookup used to match the first user who had no wiki account.
        User::factory()->administrator()->create(['mediawiki' => null]);

        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/menus');
    }

    public function testCoordinatorOfOneNetwork(): void
    {
        // The menu links straight to their network. Looking that up on the logged-in user failed, because the
        // wiki's server asks without logging in.
        $network = Network::factory()->create(['name' => 'Repair Network']);
        $user = User::factory()->networkCoordinator()->create(['mediawiki' => 'Coordinator-Wiki']);
        $network->addCoordinator($user);

        $response = $this->get('/user/menus?wiki_username=Coordinator-Wiki');

        $response->assertSuccessful();
        $items = $response->json('Administrator.items');
        $label = __('networks.general.particular_network', ['networkName' => 'Repair Network']);
        $this->assertEquals(route('networks.show', $network->id), $items[$label]);
    }
}

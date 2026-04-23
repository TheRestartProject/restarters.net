<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
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
                    1 => 'Reporting',
                    2 => 'General',
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
                    1 => 'Reporting',
                    2 => 'General',
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
                    0 => 'Reporting',
                    1 => 'General',
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

        Auth::logout();
        session()->flush();
        $this->actingAs($user);

        $response = $this->get('/user/menus');
        $menus = json_decode($response->getContent(), true);

        $this->assertEquals($present, array_keys($menus));

        if ($adminMenu && count($adminMenu)) {
            $this->assertEquals($adminMenu, array_keys($menus['Administrator']['items']));
        }
    }

    public function testLoggedOut(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/menus');
    }
}

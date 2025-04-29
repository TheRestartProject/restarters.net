<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\User;
use App\Models\UsersPermissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class MenusTest extends TestCase
{
    public static function provider()
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

    #[DataProvider('provider')]
    public function testSections($role, $present, $translator, $adminMenu): void
    {
        $user = User::factory()->{lcfirst($role)}()->create();
        
        // Set a mediawiki username that we can pass in the request
        $user->mediawiki = 'test_wiki_user';
        $user->save();

        if ($translator) {
            $up = new UsersPermissions();
            $up->user_id = $user->id;
            $up->permission_id = 6;
            $up->save();
        }

        $this->actingAs($user);

        // Pass the wiki_username parameter to the endpoint
        $response = $this->get('/user/menus?wiki_username=test_wiki_user');
        $menus = json_decode($response->getContent(), true);

        $this->assertEquals($present, array_keys($menus), "Menu sections don't match for role: $role");

        if ($adminMenu && count($adminMenu) && isset($menus['Administrator']) && isset($menus['Administrator']['items'])) {
            $this->assertEquals($adminMenu, array_keys($menus['Administrator']['items']), "Admin menu items don't match for role: $role");
        }
    }

    public function testLoggedOut(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/menus?wiki_username=nonexistent_user');
    }
}

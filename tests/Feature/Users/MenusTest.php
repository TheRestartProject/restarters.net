<?php

namespace Tests\Feature;

use App\Network;
use App\User;
use App\UsersPermissions;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * The wiki's server (wiki.restarters.net) builds its header from /user/menus and /user/thumbnail, asking for the
 * wiki user named in ?wiki_username=. Nobody is logged in when it asks.
 */
class MenusTest extends TestCase
{
    public function provider(): array
    {
        return [
            [
                'Administrator',
                ['Administrator', 'General'],
                [
                    'Brands' => '/brands',
                    'Skills' => '/skills',
                    'Group tags' => '/tags',
                    'Categories' => '/category',
                    'Users' => '/user/all',
                    'Roles' => '/role',
                    'Networks' => '/networks',
                ],
            ],
            [
                'NetworkCoordinator',
                ['Administrator', 'General'],
                ['Networks' => '/networks'],
            ],
            ['Host', ['General'], null],
            ['Restarter', ['General'], null],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testSections($role, $present, $adminMenu): void
    {
        $user = User::factory()->{lcfirst($role)}()->create(['mediawiki' => 'Wiki-'.$role]);
        $this->askAsTheWiki();

        $response = $this->get('/user/menus?wiki_username=Wiki-'.$role);

        $response->assertSuccessful();
        $menus = $response->json();
        $this->assertEquals($present, array_keys($menus));

        if ($adminMenu) {
            $this->assertEquals(array_map('url', $adminMenu), $menus['Administrator']['items']);
        }

        // The pages the Nuxt client serves: /logout and the account tab of the profile page.
        $this->assertEquals([
            __('general.profile') => url('/profile/edit/'.$user->id),
            __('auth.change_password') => url('/profile/edit/'.$user->id.'#change-password'),
            __('general.logout') => url('/logout'),
        ], $menus['General']['items']);
    }

    public function testTranslatorGetsNoTranslationsLink(): void
    {
        // The translation manager isn't one of the pages the site serves, and the site's own navbar has no link
        // to it.
        $user = User::factory()->host()->create(['mediawiki' => 'Translator-Wiki']);
        $up = new UsersPermissions();
        $up->user_id = $user->id;
        $up->permission_id = 6;
        $up->save();
        $this->askAsTheWiki();

        $response = $this->get('/user/menus?wiki_username=Translator-Wiki');

        $this->assertEquals(['General'], array_keys($response->json()));
    }

    public function testCoordinatorOfOneNetwork(): void
    {
        // The menu links straight to their network.
        $network = Network::factory()->create(['name' => 'Repair Network']);
        $user = User::factory()->networkCoordinator()->create(['mediawiki' => 'Coordinator-Wiki']);
        $network->addCoordinator($user);
        $this->askAsTheWiki();

        $response = $this->get('/user/menus?wiki_username=Coordinator-Wiki');

        $response->assertSuccessful();
        $label = __('networks.general.particular_network', ['networkName' => 'Repair Network']);
        $this->assertEquals([$label => url('/networks/'.$network->id)], $response->json('Administrator.items'));
    }

    public function testUnknownWikiUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/menus?wiki_username=Nobody');
    }

    public function testNoWikiUserNamed(): void
    {
        // Without a name the lookup would match the first user who has no wiki account.
        User::factory()->administrator()->create(['mediawiki' => null]);
        $this->askAsTheWiki();

        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/menus');
    }

    public function testThumbnail(): void
    {
        User::factory()->restarter()->create(['mediawiki' => 'Photo-Wiki']);
        $this->askAsTheWiki();

        $response = $this->get('/user/thumbnail?wiki_username=Photo-Wiki');

        $response->assertSuccessful();
        $this->assertEquals(config('app.url').'/images/placeholder-avatar.png', $response->json());
    }

    public function testThumbnailWithoutAWikiUserNamed(): void
    {
        User::factory()->restarter()->create(['mediawiki' => null]);
        $this->askAsTheWiki();

        $this->expectException(NotFoundHttpException::class);
        $this->get('/user/thumbnail');
    }

    private function askAsTheWiki(): void
    {
        Auth::logout();
        session()->flush();
    }
}

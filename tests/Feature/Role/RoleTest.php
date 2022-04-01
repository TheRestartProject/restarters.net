<?php

namespace Tests\Feature\Dashboard;

use App\Providers\RouteServiceProvider;
use App\Role;
use DB;
use Hash;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function testLoggedOut() {
        $this->expectException(AuthenticationException::class);
        $response = $this->get('/role');
    }

    public function testNotAdmin() {
        $this->loginAsTestUser(Role::RESTARTER);
        $response = $this->get('/role');
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function testBasic() {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Should see a list with edit links.
        $response = $this->get('/role');
        $response->assertSee('<a href="/role/edit/3" title="edit role permissions">Host</a>');

        // Get Edit page.  Should see a list of permissions with permission 4 (Create Party) checked and 6 (Manage
        // Translations) not checked.
        $response = $this->get('/role/edit/3');
        $response->assertSee('name="permissions[4]" checked');
        $response->assertSee('name="permissions[6]"  ');

        // Post a change to enable 6.
        $crawler = new Crawler($response->getContent());

        $tokens = $crawler->filter('input[name=_token]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $tokenValue = $tokens[0]->attr('value');

        $tokens = $crawler->filter('input[name=formId]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $formId = $tokens[0]->attr('value');

        $response = $this->post('/role/edit/3', [
            '_token' => $tokenValue,
            'formId' => $formId,
            'permissions' => [
                '4' => 4,
                '6' => 6
            ]
        ]);

        $response = $this->get('/role/edit/3');
        $response->assertSee('name="permissions[4]" checked');
        $response->assertSee('name="permissions[6]" checked');

        // Remove it again.
        $crawler = new Crawler($response->getContent());

        $tokens = $crawler->filter('input[name=_token]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $tokenValue = $tokens[0]->attr('value');

        $tokens = $crawler->filter('input[name=formId]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $formId = $tokens[0]->attr('value');

        $response = $this->post('/role/edit/3', [
            '_token' => $tokenValue,
            'formId' => $formId,
            'permissions' => [
                '4' => 4,
            ]
        ]);

        $response = $this->get('/role/edit/3');
        $response->assertSee('name="permissions[4]" checked');
        $response->assertSee('name="permissions[6]"  ');
    }
}

<?php

namespace Tests\Feature\Dashboard;

use App\Role;
use DB;
use Hash;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function testBasic(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // We should see a category we set up in TestCase.
        $response = $this->get('/category');
        $response->assertSuccessful();
        $response->assertSee('Cat1');

        // Get the edit page.
        $response = $this->get('/category/edit/111');
        $response->assertSuccessful();

        // Make a change.
        $crawler = new Crawler($response->getContent());

        $tokens = $crawler->filter('input[name=_token]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $tokenValue = $tokens[0]->attr('value');

        $response = $this->post('/category/edit/111', [
            '_token' => $tokenValue,
            'categories_desc' => 'Test category edit'
        ]);

        $this->assertTrue($response->isRedirection());
        $response->assertSessionHas('success');
    }

    public function testErrors(): void {
        $this->loginAsTestUser(Role::RESTARTER);

        $response = $this->get('/category/edit/111');
        $response->assertRedirect('/user/forbidden');

        $response = $this->post('/category/edit/111', [
            '_token' => 'test',
            'categories_desc' => 'Test category edit'
        ]);
        $response->assertRedirect('/user/forbidden');
    }
}

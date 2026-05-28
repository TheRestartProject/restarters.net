<?php

namespace Tests\Feature\Brands;

use App\Brands;
use App\Role;
use Tests\TestCase;

class BrandsTest extends TestCase
{
    public function testBrandsAdminPageRendersForAdministrator(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        Brands::factory()->create(['brand_name' => 'UT Brand']);

        $response = $this->get('/brands');
        $response->assertOk();
        // Page hosts the Vue admin SPA - the brand should be in the JSON-encoded prop
        $response->assertSee('BrandsPage', false);
        $response->assertSee('UT Brand', false);
    }

    public function testLegacyEditUrlServesAdminPage(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $brand = Brands::factory()->create(['brand_name' => 'Legacy Bookmark']);

        // /brands/edit/{id} used to render a server-side form; we now redirect bookmarks
        // through the SPA so the user lands on the admin page.
        $response = $this->get('/brands/edit/' . $brand->id);
        $response->assertOk();
        $response->assertSee('BrandsPage', false);
    }

    public function testBrandsAdminPageForbiddenForRestarter(): void
    {
        $this->loginAsTestUser(Role::RESTARTER);

        $response = $this->get('/brands');
        $response->assertRedirect('/user/forbidden');
    }
}

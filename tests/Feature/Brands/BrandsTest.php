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

        $brand = Brands::factory()->create(['brand_name' => 'UT Brand']);

        $response = $this->get('/brands');
        $response->assertOk();
        $html = $response->getContent();

        // Should host the Vue admin SPA
        $this->assertStringContainsString('<BrandsPage', $html);

        // The brand should appear in the JSON-encoded :initial-brands prop, not just anywhere
        // on the page (which would be true even for breadcrumb / nav matches).
        $this->assertMatchesRegularExpression(
            '/:initial-brands="\[[^"]*&quot;brand_name&quot;:&quot;UT Brand&quot;[^"]*\]"/',
            $html,
            'Expected the brand to appear inside the :initial-brands prop'
        );

        // No edit modal pre-opened when arriving at /brands
        $this->assertStringContainsString(':initial-edit-id="null"', $html);
    }

    public function testLegacyEditUrlPreOpensEditModalForBrand(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $brand = Brands::factory()->create(['brand_name' => 'Legacy Bookmark']);

        // /brands/edit/{id} used to render a server-side form; we now route bookmarks
        // through the SPA and pass the id so the edit modal opens for the right brand.
        $response = $this->get('/brands/edit/' . $brand->id);
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<BrandsPage', $html);
        $this->assertStringContainsString(':initial-edit-id="' . $brand->id . '"', $html);
    }

    public function testBrandsAdminPageForbiddenForRestarter(): void
    {
        $this->loginAsTestUser(Role::RESTARTER);

        $response = $this->get('/brands');
        $response->assertRedirect('/user/forbidden');
    }
}

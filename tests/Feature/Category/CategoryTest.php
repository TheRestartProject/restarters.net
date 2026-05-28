<?php

namespace Tests\Feature\Category;

use App\Role;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoriesAdminPageRendersWithVueData(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->get('/category');
        $response->assertSuccessful();
        $html = $response->getContent();

        $this->assertStringContainsString('<CategoriesPage', $html);
        $this->assertMatchesRegularExpression(
            '/:initial-categories="\[[^"]*&quot;name&quot;:&quot;Cat1&quot;[^"]*\]"/',
            $html,
            'Expected category Cat1 to be hydrated into :initial-categories'
        );
        $this->assertStringContainsString(':initial-edit-id="null"', $html);
        $this->assertStringContainsString(':clusters=', $html);
        $this->assertStringContainsString(':reliability-options=', $html);
    }

    public function testLegacyEditUrlPreOpensEditModalForCategory(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->get('/category/edit/111');
        $response->assertSuccessful();
        $html = $response->getContent();

        $this->assertStringContainsString('<CategoriesPage', $html);
        $this->assertStringContainsString(':initial-edit-id="111"', $html);
    }
}

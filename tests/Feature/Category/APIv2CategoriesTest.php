<?php

namespace Tests\Feature\Category;

use App\Category;
use App\User;
use Tests\TestCase;

class APIv2CategoriesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testListCategoriesPublic(): void
    {
        // TestCase setUp() creates several categories already (cat1, cat2, cat3, mobile, misc, desktopComputer).
        $response = $this->get('/api/v2/categories');
        $response->assertSuccessful();

        $data = $response->json('data');
        $this->assertIsArray($data);
        $names = array_column($data, 'name');
        $this->assertContains('Cat1', $names);
        $this->assertContains('Cat2', $names);
    }

    public function testListIncludesAdminFields(): void
    {
        $response = $this->get('/api/v2/categories');
        $row = collect($response->json('data'))->firstWhere('name', 'Cat2');

        $this->assertNotNull($row, 'Cat2 should be in the list');
        $this->assertArrayHasKey('weight', $row);
        $this->assertArrayHasKey('footprint', $row);
        $this->assertArrayHasKey('footprint_reliability', $row);
        $this->assertArrayHasKey('cluster', $row);
        $this->assertArrayHasKey('cluster_name', $row);
        $this->assertArrayHasKey('description_short', $row);
    }

    public function testGetSingleCategory(): void
    {
        $response = $this->getJson('/api/v2/categories/222');
        $response->assertSuccessful();
        $data = $response->json('data');

        $this->assertEquals(222, $data['id']);
        $this->assertEquals('Cat2', $data['name']);
        $this->assertEquals(2.0, $data['weight']);
        $this->assertEquals(2.0, $data['footprint']);
        $this->assertTrue($data['powered']);
    }

    public function testGetMissingCategoryReturns404(): void
    {
        $response = $this->getJson('/api/v2/categories/99999999');
        $response->assertStatus(404);
    }

    public function testUpdateCategoryAsAdmin(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson('/api/v2/categories/222?api_token=admin1', [
            'name' => 'Cat2-Renamed',
            'weight' => 12.5,
            'footprint' => 150.25,
            'footprint_reliability' => 4,
            'cluster' => 1,
            'description_short' => 'Renamed by test',
        ]);
        $response->assertSuccessful();
        $this->assertDatabaseHas('categories', [
            'idcategories' => 222,
            'name' => 'Cat2-Renamed',
            'weight' => 12.5,
            'footprint' => 150.25,
            'footprint_reliability' => 4,
            'cluster' => 1,
            'description_short' => 'Renamed by test',
        ]);
    }

    public function testUpdateCategoryRequiresAuth(): void
    {
        $response = $this->putJson('/api/v2/categories/222', [
            'name' => 'NoAuth',
            'weight' => 1,
            'footprint' => 1,
            'footprint_reliability' => 6,
        ]);
        $response->assertStatus(401);
    }

    public function testUpdateCategoryForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->putJson('/api/v2/categories/222?api_token=r1', [
            'name' => 'NoTouch',
            'weight' => 1,
            'footprint' => 1,
            'footprint_reliability' => 6,
        ]);
        $response->assertStatus(403);
    }

    public function testUpdateCategoryValidationFails(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        // Name required
        $this->putJson('/api/v2/categories/222?api_token=admin1', [])
            ->assertStatus(422);

        // Weight must be numeric & non-negative
        $this->putJson('/api/v2/categories/222?api_token=admin1', [
            'name' => 'x',
            'weight' => -5,
            'footprint' => 1,
            'footprint_reliability' => 1,
        ])->assertStatus(422);

        // Reliability must be 1-6
        $this->putJson('/api/v2/categories/222?api_token=admin1', [
            'name' => 'x',
            'weight' => 1,
            'footprint' => 1,
            'footprint_reliability' => 99,
        ])->assertStatus(422);
    }

    public function testUpdateMissingCategoryReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson('/api/v2/categories/99999999?api_token=admin1', [
            'name' => 'Ghost',
            'weight' => 1,
            'footprint' => 1,
            'footprint_reliability' => 6,
        ]);
        $response->assertStatus(404);
    }

    public function testListCategoryClustersPublic(): void
    {
        $response = $this->get('/api/v2/category-clusters');
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data, 'There should be some clusters defined in the DB');
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
    }
}

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

    public function testListIncludesTheWeightsThatCount(): void
    {
        Category::factory()->create(['idcategories' => 901, 'revision' => 2, 'name' => 'Weighed', 'powered' => 1, 'weight' => 1.28, 'footprint' => 17.5]);
        Category::factory()->create(['idcategories' => 902, 'revision' => 2, 'name' => 'Unweighed', 'powered' => 0, 'weight' => 0, 'footprint' => 0]);
        $this->app->instance(\App\Services\TypicalMiscWeights::class, new class extends \App\Services\TypicalMiscWeights {
            public function get(bool $powered): float
            {
                return $powered ? 2 : 0.75;
            }
        });

        $rows = collect($this->get('/api/v2/categories')->json('data'))->keyBy('name');

        // A weight counts between a hundredth of the category weight and 10 times it.
        $this->assertEquals(1.28, $rows['Weighed']['typical_weight']);
        $this->assertEquals(0.0128, $rows['Weighed']['weight_min']);
        $this->assertEquals(12.8, $rows['Weighed']['weight_max']);
        // Misc uses the typical Misc weight, up to 20 times it.
        $this->assertEquals(0.75, $rows['Unweighed']['typical_weight']);
        $this->assertEquals(0.001, $rows['Unweighed']['weight_min']);
        $this->assertEquals(15, $rows['Unweighed']['weight_max']);
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

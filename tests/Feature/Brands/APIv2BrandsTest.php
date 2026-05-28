<?php

namespace Tests\Feature\Brands;

use App\Brands;
use App\User;
use Tests\TestCase;

class APIv2BrandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Re-enable the JSON exception handler so middleware-thrown auth/auth-z/not-found
        // exceptions are converted into the right HTTP status codes for an API client.
        $this->withExceptionHandling();
    }

    public function testListBrandsPublic(): void
    {
        Brands::factory()->create(['brand_name' => 'BrandAlpha']);
        Brands::factory()->create(['brand_name' => 'BrandBeta']);

        $response = $this->get('/api/v2/brands');
        $response->assertSuccessful();

        $data = $response->json('data');
        $this->assertIsArray($data);
        $names = array_column($data, 'brand_name');
        $this->assertContains('BrandAlpha', $names);
        $this->assertContains('BrandBeta', $names);
    }

    public function testListBrandsOrderedAlphabetically(): void
    {
        Brands::factory()->create(['brand_name' => 'Zebra']);
        Brands::factory()->create(['brand_name' => 'Apple']);
        Brands::factory()->create(['brand_name' => 'Mango']);

        $response = $this->get('/api/v2/brands');
        $response->assertSuccessful();
        $names = array_column($response->json('data'), 'brand_name');

        // Filter to just our test data (in case of other brands in db)
        $names = array_values(array_filter($names, fn ($n) => in_array($n, ['Zebra', 'Apple', 'Mango'])));
        $this->assertEquals(['Apple', 'Mango', 'Zebra'], $names);
    }

    public function testGetSingleBrand(): void
    {
        $brand = Brands::factory()->create(['brand_name' => 'Foo']);

        $response = $this->get("/api/v2/brands/{$brand->id}");
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertEquals('Foo', $data['brand_name']);
        $this->assertEquals($brand->id, $data['id']);
    }

    public function testGetMissingBrandReturns404(): void
    {
        $response = $this->getJson('/api/v2/brands/99999999');
        $response->assertStatus(404);
    }

    public function testCreateBrandAsAdmin(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/brands?api_token=admin1', [
            'brand_name' => 'NewBrand',
        ]);
        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertEquals('NewBrand', $data['brand_name']);
        $this->assertDatabaseHas('brands', ['brand_name' => 'NewBrand']);
    }

    public function testCreateBrandRequiresAuth(): void
    {
        $response = $this->postJson('/api/v2/brands', ['brand_name' => 'NoAuth']);
        $response->assertStatus(401);
    }

    public function testCreateBrandForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'restarter1']);
        $this->actingAs($user);

        $response = $this->postJson('/api/v2/brands?api_token=restarter1', [
            'brand_name' => 'Forbidden',
        ]);
        $response->assertStatus(403);
    }

    public function testCreateBrandValidationFails(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/brands?api_token=admin1', []);
        $response->assertStatus(422);

        $response = $this->postJson('/api/v2/brands?api_token=admin1', ['brand_name' => '']);
        $response->assertStatus(422);
    }

    public function testCreateBrandRejectsDuplicate(): void
    {
        Brands::factory()->create(['brand_name' => 'Existing']);
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/brands?api_token=admin1', [
            'brand_name' => 'Existing',
        ]);
        $response->assertStatus(422);
    }

    public function testUpdateBrandAsAdmin(): void
    {
        $brand = Brands::factory()->create(['brand_name' => 'OldName']);
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson("/api/v2/brands/{$brand->id}?api_token=admin1", [
            'brand_name' => 'RenamedBrand',
        ]);
        $response->assertSuccessful();
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'brand_name' => 'RenamedBrand']);
    }

    public function testUpdateBrandRequiresAuth(): void
    {
        $brand = Brands::factory()->create();
        $response = $this->putJson("/api/v2/brands/{$brand->id}", ['brand_name' => 'X']);
        $response->assertStatus(401);
    }

    public function testUpdateBrandForbiddenForNonAdmin(): void
    {
        $brand = Brands::factory()->create();
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->putJson("/api/v2/brands/{$brand->id}?api_token=r1", [
            'brand_name' => 'NoTouch',
        ]);
        $response->assertStatus(403);
    }

    public function testUpdateMissingBrandReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);
        $response = $this->putJson('/api/v2/brands/99999999?api_token=admin1', [
            'brand_name' => 'Ghost',
        ]);
        $response->assertStatus(404);
    }

    public function testDeleteBrandAsAdmin(): void
    {
        $brand = Brands::factory()->create();
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->delete("/api/v2/brands/{$brand->id}?api_token=admin1");
        $response->assertSuccessful();
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function testDeleteBrandRequiresAuth(): void
    {
        $brand = Brands::factory()->create();
        $response = $this->deleteJson("/api/v2/brands/{$brand->id}");
        $response->assertStatus(401);
    }

    public function testDeleteBrandForbiddenForNonAdmin(): void
    {
        $brand = Brands::factory()->create();
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->delete("/api/v2/brands/{$brand->id}?api_token=r1");
        $response->assertStatus(403);
    }
}

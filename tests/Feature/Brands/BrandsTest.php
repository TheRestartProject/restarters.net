<?php

namespace Tests\Feature\Dashboard;

use App\Brands;
use App\Role;
use DB;
use Hash;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class BrandsTest extends TestCase
{
    public function testBasic(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Create a brand.
        $response = $this->post('/brands/create', [
            'brand_name' => 'UT Brand'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Should be listed.
        $response = $this->get('/brands');
        $response->assertSee('UT Brand');

        // Edit it.
        $brand = Brands::latest()->first();
        $response = $this->get('/brands/edit/' . $brand->id);
        $response->assertSee('UT Brand');

        $response = $this->post('/brands/edit/' . $brand->id, [
            'brand-name' => 'UT Brand2'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // New name should show.
        $response = $this->get('/brands');
        $response->assertSee('UT Brand2');

        // Delete
        $response = $this->get('/brands/delete/' . $brand->id);
        $response->assertRedirect();
        $response->assertSessionHas('message');
    }

    public function testErrors(): void {
        $this->loginAsTestUser(Role::RESTARTER);

        $response = $this->post('/brands/create', [
            'brand_name' => 'UT Brand'
        ]);
        $response->assertRedirect('/user/forbidden');

        $response = $this->get('/brands');
        $response->assertRedirect('/user/forbidden');

        $response = $this->get('/brands/edit/1');
        $response->assertRedirect('/user/forbidden');

        $response = $this->post('/brands/edit/1', [
            'brand-name' => 'UT Brand2'
        ]);
        $response->assertRedirect('/user/forbidden');

        $response = $this->get('/brands/delete/1');
        $response->assertRedirect('/user/forbidden');
    }
}

<?php

namespace Tests\Swagger;

use DB;
use Tests\TestCase;

class DiscourseTest extends TestCase {
    public function testSwaggerGenerate() {
        // Check we can generate the docs.
        $this->artisan('l5-swagger:generate')->assertExitCode(0);
        $response = $this->get('/apiv2/documentation');

        // Check the route fetches - we can't check the actual text as it is rendered in JS.
        $response->assertSee('SwaggerUIBundle');
    }
}
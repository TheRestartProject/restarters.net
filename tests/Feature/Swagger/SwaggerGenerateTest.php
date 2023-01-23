<?php

namespace Tests\Swagger;

use DB;
use Tests\TestCase;

class DiscourseTest extends TestCase {
    public function testSwaggerGenerate() {
        $this->artisan('l5-swagger:generate')->assertExitCode(0);
    }
}
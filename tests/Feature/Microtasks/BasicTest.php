<?php

namespace Tests\Feature\Microtasks;

use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase {
    public function testMicrotasksPageLoads() {
        // Test the dashboard page loads.  Most of the work is done inside Vue, so a basic test is just that the
        // Vue component exists.
        $this->loginAsTestUser();
        $response = $this->get('/workbench');
        $content = $response->getContent();

        $response->assertSee('<MicrotaskingPage');
    }
}

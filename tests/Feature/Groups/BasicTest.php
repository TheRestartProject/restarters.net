<?php

namespace Tests\Feature\Groups;

use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase {
    public function testPageLoads() {
        // Test the dashboard page loads.  Most of the work is done inside Vue, so a basic test is just that the
        // Vue component exists.
        $this->loginAsTestUser();
        $response = $this->get('/group');
        $content = $response->getContent();

        $response->assertSee('<GroupsPage');
    }
}

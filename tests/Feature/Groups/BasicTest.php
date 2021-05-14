<?php

namespace Tests\Feature\Groups;

use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase {
    public function testDashboardPageLoads() {
        // Test the dashboard page loads.  Most of the work is done inside Vue, so a basic test is just that the
        // Vue component exists.
        $this->loginAsTestUser();
        $response = $this->get('/groups');
        $content = $response->getContent();

        $this->assertNotFalse(strpos($content, '<GroupsPage'));
    }
}

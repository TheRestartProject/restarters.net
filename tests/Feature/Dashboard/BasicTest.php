<?php

namespace Tests\Feature\Dashboard;

use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testPageLoads()
    {
        // Test the dashboard page loads.  Most of the work is done inside Vue, so a basic test is just that the
        // Vue component exists.
        $this->loginAsTestUser();
        $response = $this->get('/dashboard');

        $this->assertVueProperties($response, [
            [
                'administrator' => 'false',
                'host' => 'false',
                'restarter' => 'true',
                'network-coordinator' => 'false',
                ':your-groups' => '[]',
                ':nearby-groups' => '[]',
                ':upcoming-events' => '[]',
                ':past-events' => 'null',
                ':topics' => '[]',
                'see-all-topics-link' => env('DISCOURSE_URL').'/latest',
                ':is-logged-in' => 'true',
                'discourse-base-url' => env('DISCOURSE_URL'),
                ':new-groups' => '[]',
            ],
        ]);
    }
}

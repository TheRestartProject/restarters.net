<?php

namespace Tests\Feature\Microtasks;

use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testMicrotasksPageLoads()
    {
        // Test the dashboard page loads.  Most of the work is done inside Vue, so a basic test is just that the
        // Vue component exists.
        $this->loginAsTestUser();
        $response = $this->get('/workbench');

        $this->assertVueProperties($response, [
            [],
            [
                // Can't assert on total-contributions dev systems might have varying info.
                'active-quest' => 'default',
                ':current-user-quests' => '0',
                ':current-user-contributions' => '0',
                'see-all-topics-link' => env('DISCOURSE_URL').'/tag/workbench/l/latest',
                ':is-logged-in' => 'true',
                'discourse-base-url' => env('DISCOURSE_URL').'',
            ],
        ]);
    }
}

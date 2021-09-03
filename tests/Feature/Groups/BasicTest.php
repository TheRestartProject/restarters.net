<?php

namespace Tests\Feature\Groups;

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
        $response = $this->get('/group');

        $this->assertVueProperties($response, [
            [
                // Can't assert on all-group-tags dev systems might have varying info.
                ':all-groups' => '[]',
                ':your-groups' => '[]',
                ':nearby-groups' => '[]',
                'your-area' => 'London',
                ':can-create' => 'false',
                ':user-id' => '1',
                'tab' => 'mine',
                ':network' => 'null',
                ':networks' => '[{"id":1,"name":"Restarters","description":null,"website":null,"default_language":"en","timezone":"Europe\\/London","created_at":"2021-05-24 12:19:37","updated_at":"2021-05-24 12:19:37","events_push_to_wordpress":0,"include_in_zapier":0,"users_push_to_drip":0,"shortname":"restarters","discourse_group":null}]',
                ':show-tags' => 'false',
            ],
        ]);
    }
}

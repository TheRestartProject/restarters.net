<?php

namespace Tests\Feature\Fixometer;

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
        $response = $this->get('/fixometer');

        // No actual cluster info in test environment.
        $clusters = json_encode([
            [
                'idclusters' => 1,
                'name' => 'Computers and Home Office',
                'categories' => [],
            ],
            [
                'idclusters' => 2,
                'name' => 'Electronic Gadgets',
                'categories' => [],
            ],
            [
                'idclusters' => 3,
                'name' => 'Home Entertainment',
                'categories' => [],
            ],
            [
                'idclusters' => 4,
                'name' => 'Kitchen and Household Items',
                'categories' => [],
            ],
            [
                'idclusters' => 5,
                'name' => 'Non-Powered Items',
                'categories' => [],
            ],
        ]);

        $this->assertVueProperties($response, [
            [
                // Can't assert on latest-data or impact-data as dev systems might have varying info.
                ':clusters' => $clusters,
                ':brands' => '[]',
                ':barrier-list' => '[{"id":1,"barrier":"Spare parts not available"},{"id":2,"barrier":"Spare parts too expensive"},{"id":3,"barrier":"No way to open the product"},{"id":4,"barrier":"Repair information not available"},{"id":5,"barrier":"Lack of equipment"}]',
                ':item-types' => '[]',
                ':is-admin' => 'false',
            ],
        ]);
    }
}

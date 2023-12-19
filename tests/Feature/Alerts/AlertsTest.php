<?php

namespace Tests\Feature\Dashboard;

use App\Alerts;
use App\Role;
use DB;
use Hash;
use Tests\TestCase;

class AlertsTest extends TestCase
{
    public function testBasic()
    {
        // List - no alerts present.
        $response = $this->get('/api/v2/alerts');
        $response->assertSuccessful();

        $json = json_decode($response->getContent(), true);
        self::assertEquals(0, count($json['data']));
    }
}

<?php

namespace Tests\Feature\Dashboard;

use DB;
use Hash;
use Tests\TestCase;

class CookiePolicyTest extends TestCase
{
    public function testPageLoads()
    {
        $response = $this->get('/about/cookie-policy');
        $response->assertSeeText('Cookie Policy');
    }
}

<?php

namespace Tests\Feature\Dashboard;

use App\Group;
use App\Party;
use App\Role;
use App\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class CookiePolicyTest extends TestCase
{
    public function testPageLoads()
    {
        $response = $this->get('/about/cookie-policy');
        $response->assertSeeText('Cookie Policy');
    }
}

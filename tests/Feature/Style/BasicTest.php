<?php

namespace Tests\Feature\Style;

use App\Group;
use App\Party;
use App\Role;
use App\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class BasicTest extends TestCase
{
    public function testPageLoads()
    {
        // Test the style guide page.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/style/guide');
        $response->assertSee('Style Guide');
        $response->assertSee('Badges');
    }

    public function testSearch() {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/style/find');
        $response->assertSee('&quot;buttons&quot;', false);
    }
}

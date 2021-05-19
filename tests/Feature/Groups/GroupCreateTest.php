<?php

namespace Tests\Feature\Groups;

use App\Role;
use Tests\TestCase;

class GroupCreateTest extends TestCase {
    public function testCreate() {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $this->assertNotNull($this->createGroup());
    }
}

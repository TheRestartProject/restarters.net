<?php

namespace Tests\Feature\Groups;

use App\Role;
use Tests\TestCase;

class GroupCreateTest extends TestCase {
    public function testCreate() {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $this->assertNotNull($this->createGroup());
    }

    public function testDuplicate() {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Test creating the same group twice.
        $this->assertNotNull($this->createGroup());

        $response = $this->post('/group/create',  [
            'name' => "Test Group0",
            'website' => 'https://therestartproject.org',
            'location' => 'London',
            'free_text' => 'Some text.'
        ]);

        $this->assertContains('That group name (Test Group0) already exists', $response->getContent());
    }
}

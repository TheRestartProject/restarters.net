<?php

namespace Tests\Feature\Groups;

use App\Role;
use Tests\TestCase;

class GroupViewTest extends TestCase {
    public function testBasic() {
        // Check we can create a group and view it.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);
        $response = $this->get("/group/view/$id");

        $this->assertVueProperties($response, [
            [
                ':idgroups' => $id,
                ':canedit' => 'true',
                ':top-devices' => '[]',
                ':events' => '[]',
            ]
        ]);
    }
}

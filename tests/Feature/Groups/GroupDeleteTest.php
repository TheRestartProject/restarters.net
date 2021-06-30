<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Role;
use Tests\TestCase;

class GroupDeleteTest extends TestCase {
    public function testDelete() {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);
        $group = Group::where('idgroups', $id)->first();
        $name = $group->name;

        // Only administrators can delete.
        foreach (['Restarter', 'Host', 'NetworkCoordinator'] as $role) {
            $user = factory(\App\User::class)->states($role)->create();
            $this->actingAs($user);
            $this->followingRedirects();
            $response = $this->get("/group/delete/$id");
            $this->assertContains('Sorry, but you do not have the permissions to perform that action', $response->getContent());
        }

        $user = factory(\App\User::class)->states('Administrator')->create();
        $this->actingAs($user);
        $this->followingRedirects();
        $response = $this->get("/group/delete/$id");
        $this->assertContains( __('groups.delete_succeeded', [
            'name' => $name
        ]), $response->getContent());
    }
}

<?php

namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\Party;
use App\Models\Role;
use Tests\TestCase;

class GroupDeleteTest extends TestCase
{
    public function testDelete(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);
        $group = Group::where('idgroups', $id)->first();
        $name = $group->name;

        // Only administrators can delete.
        foreach (['Restarter', 'Host', 'NetworkCoordinator'] as $role) {
            $user = \App\Models\User::factory()->{lcfirst($role)}()->create();
            $this->actingAs($user);
            $this->followingRedirects();
            $response = $this->get("/group/delete/$id");
            $this->assertStringContainsString('Sorry, but you do not have the permissions to perform that action', $response->getContent());
        }

        $user = \App\Models\User::factory()->administrator()->create();
        $this->actingAs($user);
        $this->followingRedirects();
        $response = $this->get("/group/delete/$id");
        $this->assertStringContainsString(__('groups.delete_succeeded', [
            'name' => $name,
        ]), $response->getContent());
    }

    public function testCanDeleteWithEmptyEvent(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);
        $group = Group::where('idgroups', $id)->first();
        $name = $group->name;

        // Add an event with no devices - should still be able to delete.
        $this->createEvent($id, 'yesterday');

        $user = \App\Models\User::factory()->administrator()->create();
        $this->actingAs($user);
        $this->followingRedirects();
        $response = $this->get("/group/delete/$id");
        $this->assertStringContainsString(__('groups.delete_succeeded', [
            'name' => $name,
        ]), $response->getContent());
    }

    public function testCantDeleteWithDevice(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        // Add an event with a device - should not  be able to delete.
        $idevents = $this->createEvent($id, 'yesterday');
        $iddevices = $this->createDevice($idevents, 'misc');

        $user = \App\Models\User::factory()->administrator()->create();
        $this->actingAs($user);
        $this->followingRedirects();
        $response = $this->get("/group/delete/$id");
        $this->assertStringContainsString('Sorry, but you do not have the permissions to perform that action.', $response->getContent());

        // Delete the event - still shouldn't be deletable as a device exists.
        Party::find($idevents)->delete();

        $response = $this->get("/group/delete/$id");
        $response->assertRedirect('/user/forbidden');
    }

    public function testCanDeleteWithDeletedEvent(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        // Create a past event
        $event = Party::factory()->moderated()->create([
                                                                        'event_start_utc' => '2000-01-01T10:15:05+05:00',
                                                                        'event_end_utc' => '2000-01-0113:45:05+05:00',
                                                                        'group' => $id,
                                                                    ]);

        // Should
        $event->delete();
        $response = $this->get("/group/delete/$id");
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}

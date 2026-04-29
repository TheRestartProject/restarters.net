<?php

namespace Tests\Feature\Groups;

use App\EventsUsers;
use App\Group;
use App\Party;
use App\Role;
use App\User;
use Tests\TestCase;

class InviteVolunteerExcludeConfirmedTest extends TestCase
{
    private function setup_group_with_event(): array
    {
        $host = User::factory()->host()->create();
        $group = Group::factory()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $event = Party::factory()->create(['group' => $group->idgroups]);

        return [$host, $group, $event];
    }

    public function testConfirmedVolunteerStatusOneIsExcluded(): void
    {
        [$host, $group, $event] = $this->setup_group_with_event();

        $volunteer = User::factory()->restarter()->create();
        $group->addVolunteer($volunteer);

        EventsUsers::create([
            'event' => $event->idevents,
            'user' => $volunteer->id,
            'status' => 1,
        ]);

        $this->actingAs($host);
        $response = $this->getJson("/api/v2/groups/{$group->idgroups}/volunteers?exclude_event={$event->idevents}");
        $response->assertOk();

        $userIds = collect($response->json('data'))->pluck('user')->toArray();
        $this->assertNotContains($volunteer->id, $userIds, 'Confirmed volunteer (status=1) should be excluded');
    }

    public function testConfirmedVolunteerStatusNullIsExcluded(): void
    {
        [$host, $group, $event] = $this->setup_group_with_event();

        $volunteer = User::factory()->restarter()->create();
        $group->addVolunteer($volunteer);

        EventsUsers::create([
            'event' => $event->idevents,
            'user' => $volunteer->id,
            'status' => null,
        ]);

        $this->actingAs($host);
        $response = $this->getJson("/api/v2/groups/{$group->idgroups}/volunteers?exclude_event={$event->idevents}");
        $response->assertOk();

        $userIds = collect($response->json('data'))->pluck('user')->toArray();
        $this->assertNotContains($volunteer->id, $userIds, 'Confirmed volunteer (status=null) should be excluded');
    }

    public function testNonConfirmedVolunteerIsNotExcluded(): void
    {
        [$host, $group, $event] = $this->setup_group_with_event();

        $volunteer = User::factory()->restarter()->create();
        $group->addVolunteer($volunteer);

        EventsUsers::create([
            'event' => $event->idevents,
            'user' => $volunteer->id,
            'status' => 0,
        ]);

        $this->actingAs($host);
        $response = $this->getJson("/api/v2/groups/{$group->idgroups}/volunteers?exclude_event={$event->idevents}");
        $response->assertOk();

        $userIds = collect($response->json('data'))->pluck('user')->toArray();
        $this->assertContains($volunteer->id, $userIds, 'Invited-but-not-confirmed volunteer should still appear');
    }
}

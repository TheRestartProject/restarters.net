<?php

namespace Tests\Unit;

use App\Device;
use App\Group;
use App\Network;
use App\Party;
use App\User;
use DB;
use App\Helpers\Fixometer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CountsTests extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        Group::truncate();
        Network::truncate();
        Party::truncate();
        DB::delete('delete from users_groups');
        DB::delete('delete from group_network');
        DB::delete('delete from user_network');
        DB::statement('SET foreign_key_checks=1');
    }

    // Event counts should only include events in the past.

    /** @test */
    public function it_can_check_if_event_counts_past()
    {
        // Create a group and an event on it.
        $network = factory(Network::class)->create();
        $group = factory(Group::class)->create();
        $network->addGroup($group);

        $event = factory(Party::class)->create([
            'group' => $group,
            'event_date' => '2130-01-01',
            'start' => '12:13',
        ]);

        // Delete all events - can hang around in the DB because the relation doesn't cascade the deletes.
        DB::delete("DELETE FROM devices WHERE event = {$event->idevents}");
        $this->assertEquals(0, count($event->devices));

        // Create a device on the event.
        $device = factory(Device::class)->states('fixed', 'mobile')->create([
            'event' => $event->idevents,
        ]);

        // Save and refetch the event to make sure that it looks how we expect.
        $event->save();
        $event2 = $event->fresh();

        $pastcount = count($event2->ofThisGroup('group', true, false));
        $allcount = count($event2->ofThisGroup('group', false, false));

        // The event we created is in the future, so the count of all should be more than the count in the past.
        $this->assertGreaterThan($pastcount, $allcount);

        // The accessor should construct the timestamp.
        $this->assertEquals('2130-01-01 12:13:00', $event2->event_timestamp);

        // The devices should be fetched on demand.
        $this->assertEquals(1, count($event2->devices));
    }
}

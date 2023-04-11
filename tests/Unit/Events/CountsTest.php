<?php

namespace Tests\Unit;

use App\Device;
use App\Group;
use App\Helpers\Fixometer;
use App\Network;
use App\Party;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CountsTest extends TestCase
{
    // Event counts should only include events in the past.

    /** @test */
    public function it_can_check_if_event_counts_past()
    {
        // Create a group and an event on it.
        $network = Network::factory()->create();
        $group = Group::factory()->create();
        $network->addGroup($group);

        // Create an event.  Starting with MySQL 8, any trailing timezone will be processed, whereas previously
        // it was ignored.  So don't add one.
        $event = Party::factory()->create([
            'group' => $group,
            'event_start_utc' => '2130-01-01T10:15:05',
            'event_end_utc' => '2130-01-01T13:45:05',
        ]);

        // Delete all events - can hang around in the DB because the relation doesn't cascade the deletes.
        DB::delete("DELETE FROM devices WHERE event = {$event->idevents}");
        $this->assertEquals(0, count($event->devices));

        // Create a device on the event.
        $device = Device::factory()->fixed()->mobile()->create([
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
        $this->assertEquals('2130-01-01 10:15', $event2->event_timestamp);

        // The devices should be fetched on demand.
        $this->assertEquals(1, count($event2->devices));
    }
}

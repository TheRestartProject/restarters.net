<?php

namespace Tests\Unit;

use App\Group;
use App\Network;
use App\Party;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        Group::truncate();
        Party::truncate();
        Network::truncate();
        DB::delete('delete from group_network');
        DB::statement('SET foreign_key_checks=1');
    }

    public function testEventsRequiringModeration(): void
    {
        // arrange
        $network = Network::factory()->create();

        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();
        $network->addGroup($group1);
        $network->addGroup($group2);

        $start = Carbon::now()->addDays(1)->toIso8601String();
        $end = Carbon::now()->addDays(2)->toIso8601String();

        $event1 = Party::factory()->create(['approved' => false, 'group' => $group1, 'event_start_utc' => $start, 'event_end_utc' => $end]);
        $event2 = Party::factory()->create(['approved' => true, 'group' => $group2, 'event_start_utc' => $start, 'event_end_utc' => $end]);

        // Past events should appear too.
        $start = Carbon::now()->subDays(1)->toIso8601String();
        $end = Carbon::now()->subDays(2)->toIso8601String();
        $event3 = Party::factory()->create(['approved' => false, 'group' => $group1, 'event_start_utc' => $start, 'event_end_utc' => $end]);
        $event4 = Party::factory()->create(['approved' => true, 'group' => $group2, 'event_start_utc' => $start, 'event_end_utc' => $end]);

        // act
        $ids = [];
        foreach ($network->eventsRequiringModeration() as $event) {
            $ids[] = $event->idevents;
        }

        // assert
        $this->assertContains($event1->idevents, $ids);
        $this->assertNotContains($event2->idevents, $ids);
        $this->assertContains($event3->idevents, $ids);
        $this->assertNotContains($event4->idevents, $ids);
    }
}

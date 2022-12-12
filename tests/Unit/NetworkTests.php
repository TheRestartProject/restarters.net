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

class NetworkTests extends TestCase
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

    /** @test */
    public function it_can_return_events_requiring_moderation()
    {
        // arrange
        $network = Network::factory()->create();

        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();
        $network->addGroup($group1);
        $network->addGroup($group2);

        $start = Carbon::now()->addDays(1)->toIso8601String();
        $end = Carbon::now()->addDays(2)->toIso8601String();

        $event1 = Party::factory()->create(['wordpress_post_id' => null, 'group' => $group1, 'event_start_utc' => $start, 'event_end_utc' => $end]);
        $event2 = Party::factory()->create(['wordpress_post_id' => 1, 'group' => $group2, 'event_start_utc' => $start, 'event_end_utc' => $end]);

        // act
        $eventsRequiringModeration = $network->eventsRequiringModeration();
        $ids = $eventsRequiringModeration->pluck('idevents');

        // assert
        $this->assertStringContainsString($event1->idevents, $ids);
        $this->assertStringNotContainsString($event2->idevents, $ids);
    }
}

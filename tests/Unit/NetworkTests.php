<?php

namespace Tests\Unit;

use App\Group;
use App\Network;
use App\Party;
use App\User;
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
        $network = factory(Network::class)->create();

        $group1 = factory(Group::class)->create();
        $group2 = factory(Group::class)->create();
        $network->addGroup($group1);
        $network->addGroup($group2);

        $event1 = factory(Party::class)->create(['wordpress_post_id' => null, 'group' => $group1]);
        $event2 = factory(Party::class)->create(['wordpress_post_id' => 1, 'group' => $group2]);

        // act
        $eventsRequiringModeration = $network->eventsRequiringModeration();
        $ids = $eventsRequiringModeration->pluck('idevents');

        // assert
        $this->assertContains($event1->idevents, $ids);
        $this->assertNotContains($event2->idevents, $ids);
    }
}

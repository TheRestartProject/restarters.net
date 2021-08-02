<?php

namespace Tests\Unit;

use App\Group;
use App\Network;
use App\Party;
use App\User;
use Carbon\Carbon;
use DB;
use App\Helpers\Fixometer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventStateTests extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        Party::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    /** @test */
    public function it_is_active_during_an_event()
    {
        // arrange
        $event = factory(Party::class)->create();
        $event->event_date = Carbon::now()->toDateString();
        $event->start = Carbon::now()->addHours(-1)->toTimeString();
        $event->end = Carbon::now()->addHours(2)->toTimeString();

        // assert
        $this->assertTrue($event->isInProgress());
    }

    /** @test */
    public function it_is_active_at_the_start_time()
    {
        // arrange
        $event = factory(Party::class)->create();
        $event->event_date = Carbon::now()->toDateString();
        $event->start = Carbon::now()->toTimeString();
        $event->end = Carbon::now()->addHours(3)->toTimeString();

        // assert
        $this->assertTrue($event->isInProgress());
    }

    // This is just temporary for Repair Together, until we have
    // proper timezone support.

    /** @test */
    public function it_is_active_an_hour_before_the_start_time()
    {
        // arrange
        $event = factory(Party::class)->create();
        $event->event_date = Carbon::now()->toDateString();
        $event->start = Carbon::now()->addHours(1)->toTimeString();
        $event->end = Carbon::now()->addHours(4)->toTimeString();

        // assert
        $this->assertTrue($event->isInProgress());
    }
}

<?php

namespace Tests\Unit;

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

class EventStateTest extends TestCase
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
        $event->event_start_utc = Carbon::now()->addHours(-1)->toIso8601String();
        $event->event_end_utc = Carbon::now()->addHours(2)->toIso8601String();

        // assert
        $this->assertTrue($event->isInProgress());
    }

    /** @test */
    public function it_is_active_at_the_start_time()
    {
        // arrange
        $event = factory(Party::class)->create();
        $event->event_start_utc = Carbon::now()->toIso8601String();
        $event->event_end_utc = Carbon::now()->addHours(3)->toIso8601String();

        // assert
        $this->assertTrue($event->isInProgress());
    }

    /** @test */
    public function it_starts_an_hour_early() {
        $event = factory(Party::class)->create();

        $event->event_start_utc = Carbon::now()->addMinutes(30)->toIso8601String();
        $event->event_end_utc = Carbon::now()->addMinutes(90)->toIso8601String();
        $this->assertTrue($event->isInProgress());
    }

    /** @test */
    public function is_doesnt_start_too_soon() {
        $event = factory(Party::class)->create();
        $event->event_start_utc = Carbon::now()->addMinutes(65)->toIso8601String();
        $event->event_end_utc = Carbon::now()->addMinutes(90)->toIso8601String();

        $this->assertFalse($event->isInProgress());
    }
}

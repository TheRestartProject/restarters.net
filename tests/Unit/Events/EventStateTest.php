<?php

namespace Tests\Unit;

use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\RepairNetworkService;
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

        $this->host = factory(User::class)->states('Administrator')->create();
        $this->actingAs($this->host);

        $this->group = factory(Group::class)->create();
        $this->group->addVolunteer($this->host);
        $this->group->makeMemberAHost($this->host);
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

    /**
     * @dataProvider timeProvider
     */
    public function testStatesOnViewPage($date, $upcoming, $finished, $inprogress, $startingsoon) {

        $idevents = $this->createEvent($this->group->idgroups, $date);

        $response = $this->get('/party/view/' . $idevents);
        $props = $this->assertVueProperties($response, [
            [],
            [
                ':idevents' => $idevents
            ]
        ]);

        $initialEvent = json_decode($props[1][':initial-event'], TRUE);

        self::assertEquals($upcoming, $initialEvent['upcoming']);
        self::assertEquals($finished, $initialEvent['finished']);
        self::assertEquals($inprogress, $initialEvent['inprogress']);
        self::assertEquals($startingsoon, $initialEvent['startingsoon']);
    }

    public function timeProvider() {
        return [
            // Past event
            [ '2000-01-01 12:00', false, true, false, false ],

            // Future event
            [ '2038-01-01 12:00', true, false, false, false ],

            // Starting soon, but more than an hour away (currently the inprogress flag gets set an hour early).
            [ Carbon::now()->addMinutes(90)->toIso8601String(), true, false, false, true ],

            // Starting less than an hour away, which currently has inprogress set.
            [ Carbon::now()->addMinutes(30)->toIso8601String(), true, false, true, false ],

            // In progress
            [ Carbon::now()->subHour()->toIso8601String(), false, false, true, false ],
        ];
    }
}

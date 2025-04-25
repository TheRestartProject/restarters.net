<?php

namespace Tests\Unit;

use App\Models\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use App\Helpers\Fixometer;
use App\Helpers\RepairNetworkService;
use App\Models\Network;
use App\Models\Party;
use App\Models\User;
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

        $this->host = User::factory()->administrator()->create();
        $this->actingAs($this->host);

        $this->group = Group::factory()->create();
        $this->group->addVolunteer($this->host);
        $this->group->makeMemberAHost($this->host);
    }

    #[Test]
    public function it_is_active_during_an_event(): void
    {
        // arrange
        $event = Party::factory()->create();
        $event->event_start_utc = Carbon::now()->addHours(-1)->toIso8601String();
        $event->event_end_utc = Carbon::now()->addHours(2)->toIso8601String();

        // assert
        $this->assertTrue($event->isInProgress());
    }

    #[Test]
    public function it_is_active_at_the_start_time(): void
    {
        // arrange
        $event = Party::factory()->create();
        $event->event_start_utc = Carbon::now()->toIso8601String();
        $event->event_end_utc = Carbon::now()->addHours(3)->toIso8601String();

        // assert
        $this->assertTrue($event->isInProgress());
    }

    #[Test]
    public function it_doesnt_start_an_hour_early(): void {
        $event = Party::factory()->create();

        $event->event_start_utc = Carbon::now()->addMinutes(30)->toIso8601String();
        $event->event_end_utc = Carbon::now()->addMinutes(90)->toIso8601String();
        $this->assertFalse($event->isInProgress());
    }

    #[Test]
    public function is_doesnt_start_too_soon(): void {
        $event = Party::factory()->create();
        $event->event_start_utc = Carbon::now()->addMinutes(65)->toIso8601String();
        $event->event_end_utc = Carbon::now()->addMinutes(90)->toIso8601String();

        $this->assertFalse($event->isInProgress());
    }

    #[DataProvider('timeProvider')]
    public function testStatesOnViewPage($date, $upcoming, $finished, $inprogress, $startingsoon): void {

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

    public function timeProvider(): array {
        return [
            // Past event
            [ '2000-01-01 12:00', false, true, false, false ],

            // Future event
            [ '2038-01-01 12:00', true, false, false, false ],

            // Starting soon, but more than an hour away .
            [ Carbon::now()->addMinutes(90)->toIso8601String(), true, false, false, true ],

            // Starting less than an hour away, no longer has inprogress set and is starting soon.
            [ Carbon::now()->addMinutes(30)->toIso8601String(), true, false, false, true ],

            // In progress
            [ Carbon::now()->subHour()->toIso8601String(), false, false, true, false ],
        ];
    }
}

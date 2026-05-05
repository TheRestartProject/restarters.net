<?php

namespace Tests\Feature\Events;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PartyIndexQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        EventsUsers::truncate();
        Party::truncate();
        UserGroups::truncate();
        Group::truncate();
        User::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    protected function tearDown(): void
    {
        DB::disableQueryLog();
        DB::flushQueryLog();
        parent::tearDown();
    }

    private function makeEvent(int $groupId, bool $past = true): Party
    {
        return Party::factory()->moderated()->create([
            'group' => $groupId,
            'event_start_utc' => $past
                ? Carbon::parse('1pm last month')->toIso8601String()
                : Carbon::parse('1pm next month')->toIso8601String(),
            'event_end_utc' => $past
                ? Carbon::parse('3pm last month')->toIso8601String()
                : Carbon::parse('3pm next month')->toIso8601String(),
        ]);
    }

    private function addDevice(Party $event): void
    {
        Device::factory()->fixed()->create([
            'category' => 11,
            'category_creation' => 11,
            'event' => $event->idevents,
        ]);
    }

    public function testPartyIndexQueryCountScalesWithO1NotN(): void
    {
        $user = $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Create 2 events across 2 groups.
        $id1 = $this->createGroup('Group A');
        $id2 = $this->createGroup('Group B');
        foreach ([$id1, $id2] as $gid) {
            $this->addDevice($this->makeEvent($gid));
        }

        // Warm up
        $this->get('/party');

        DB::enableQueryLog();
        $this->get('/party')->assertSuccessful();
        $queriesFor2Events = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        // Add 6 more events (total 8).
        $id3 = $this->createGroup('Group C');
        foreach ([$id1, $id1, $id2, $id2, $id3, $id3] as $gid) {
            $this->addDevice($this->makeEvent($gid));
        }

        DB::enableQueryLog();
        $this->get('/party')->assertSuccessful();
        $queriesFor8Events = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        $this->assertLessThan(
            $queriesFor2Events * 1.5,
            $queriesFor8Events,
            "Query count grew too much — N+1 in PartyController::index or expandEvent. 2 events: $queriesFor2Events, 8 events: $queriesFor8Events"
        );
    }
}

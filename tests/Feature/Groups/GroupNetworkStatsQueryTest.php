<?php

namespace Tests\Feature\Groups;

use App\Device;
use App\Group;
use App\Helpers\RepairNetworkService;
use App\Network;
use App\Party;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GroupNetworkStatsQueryTest extends TestCase
{
    private RepairNetworkService $networkService;
    private User $coordinator;
    private Network $network;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        Party::truncate();
        Group::truncate();
        User::truncate();
        DB::statement('SET foreign_key_checks=1');

        $this->networkService = new RepairNetworkService();
        $this->coordinator = User::factory()->networkCoordinator()->create(['api_token' => 'test-token']);
        $this->network = Network::factory()->create();
        $this->network->addCoordinator($this->coordinator);
    }

    protected function tearDown(): void
    {
        DB::disableQueryLog();
        DB::flushQueryLog();
        parent::tearDown();
    }

    private function addGroupWithEvents(int $eventCount = 2): Group
    {
        $admin = User::factory()->administrator()->create();
        $group = Group::factory()->create(['latitude' => 51.5, 'longitude' => -0.12]);
        $this->networkService->addGroupToNetwork($admin, $group, $this->network);

        for ($i = 0; $i < $eventCount; $i++) {
            $event = Party::factory()->moderated()->create([
                'group' => $group->idgroups,
                'event_start_utc' => Carbon::parse('1pm last month')->subDays($i)->toIso8601String(),
                'event_end_utc'   => Carbon::parse('3pm last month')->subDays($i)->toIso8601String(),
            ]);
            Device::factory()->fixed()->create([
                'category' => 11,
                'category_creation' => 11,
                'event' => $event->idevents,
            ]);
        }

        return $group;
    }

    public function testGroupNetworkStatsQueryCountDoesNotScaleWithGroupCount(): void
    {
        $this->actingAs($this->coordinator);

        // Create 3 groups each with 2 past events and devices.
        for ($i = 0; $i < 3; $i++) {
            $this->addGroupWithEvents(2);
        }

        // Warm up caches.
        $this->get('/api/groups/network?api_token=test-token')->assertSuccessful();

        DB::enableQueryLog();
        $this->get('/api/groups/network?api_token=test-token')->assertSuccessful();
        $queriesFor3Groups = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        // Add 3 more groups (total 6).
        for ($i = 0; $i < 3; $i++) {
            $this->addGroupWithEvents(2);
        }

        DB::enableQueryLog();
        $this->get('/api/groups/network?api_token=test-token')->assertSuccessful();
        $queriesFor6Groups = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        $this->assertLessThan(
            $queriesFor3Groups * 1.5,
            $queriesFor6Groups,
            "Query count scaled with group count — N+1 in getGroupsByUsersNetworks. 3 groups: $queriesFor3Groups, 6 groups: $queriesFor6Groups"
        );
    }
}

<?php

namespace Tests\Feature\Fixometer;

use App\Device;
use App\Party;
use App\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceApiQueryTest extends TestCase
{
    private function createDevicesForGroup(int $groupId, int $count): void
    {
        $event = Party::factory()->moderated()->create([
            'group' => $groupId,
            'event_start_utc' => Carbon::parse('1pm last month')->toIso8601String(),
            'event_end_utc' => Carbon::parse('3pm last month')->toIso8601String(),
        ]);

        for ($i = 0; $i < $count; $i++) {
            Device::factory()->fixed()->create([
                'category' => 11,
                'category_creation' => 11,
                'event' => $event->idevents,
            ]);
        }
    }

    public function testDeviceApiQueryCountScalesWithO1NotN(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $id1 = $this->createGroup('Group A');
        $this->createDevicesForGroup($id1, 5);

        // Warm up (cache, lazy-loaded config, etc.)
        $this->get('/api/devices/1/5?sortBy=iddevices&sortDesc=asc&powered=true');

        DB::enableQueryLog();
        $response = $this->get('/api/devices/1/5?sortBy=iddevices&sortDesc=asc&powered=true');
        $response->assertSuccessful();
        $queriesFor5 = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        $json = json_decode($response->getContent(), true);
        $this->assertEquals(5, $json['count'], 'Expected 5 devices — check category/powered setup');

        // Add 15 more devices across another group.
        $id2 = $this->createGroup('Group B');
        $this->createDevicesForGroup($id2, 15);

        DB::enableQueryLog();
        $response = $this->get('/api/devices/1/20?sortBy=iddevices&sortDesc=asc&powered=true');
        $response->assertSuccessful();
        $queriesFor20 = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        $json = json_decode($response->getContent(), true);
        $this->assertEquals(20, $json['count'], 'Expected 20 devices — check category/powered setup');

        $this->assertLessThan(
            $queriesFor5 * 2,
            $queriesFor20,
            "Query count grew too much — likely N+1 in Device resource. 5 devices: $queriesFor5, 20 devices: $queriesFor20"
        );
    }

    public function testFixometerPageQueryCountScalesWithO1NotN(): void
    {
        // The /fixometer page has a per-group N+1 for user_groups.
        $user = $this->loginAsTestUser(Role::ADMINISTRATOR);

        $id1 = $this->createGroup('Group A');
        $this->createDevicesForGroup($id1, 2);

        // Warm up
        $this->get('/fixometer');

        DB::enableQueryLog();
        $this->get('/fixometer')->assertSuccessful();
        $queriesFor1Group = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        // Join 4 more groups.
        for ($i = 0; $i < 4; $i++) {
            $id = $this->createGroup("Extra Group $i");
            \App\UserGroups::create([
                'user' => $user->id,
                'group' => $id,
                'role' => Role::HOST,
                'status' => 1,
            ]);
        }

        DB::enableQueryLog();
        $this->get('/fixometer')->assertSuccessful();
        $queriesFor5Groups = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        $this->assertLessThan(
            $queriesFor1Group * 1.5,
            $queriesFor5Groups,
            "Query count grew too much when user joined more groups — N+1 in DeviceController::index. 1 group: $queriesFor1Group, 5 groups: $queriesFor5Groups"
        );
    }
}

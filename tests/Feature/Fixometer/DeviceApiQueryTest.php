<?php

namespace Tests\Feature\Fixometer;

use App\Device;
use App\Group;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceApiQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
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

    // testFixometerPageQueryCountScalesWithO1NotN was a query-count regression
    // check for the /fixometer page (a per-group N+1 for user_groups). That
    // route is retired under the Nuxt cutover — the Fixometer page is now
    // client-side in the SPA, so the page-level query-count concern is moot.
    // The equivalent concern for the live /api/devices endpoint is covered
    // by testDeviceApiQueryCountScalesWithO1NotN above.
}

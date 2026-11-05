<?php

namespace Tests\Feature\Devices;

use App\Device;
use App\Party;
use App\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2DevicesListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function createEventAsHost(User $host): array
    {
        $this->actingAs($host);
        $idgroups = $this->createGroup('Devices List Test Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);

        return [$idgroups, $idevents];
    }

    public function testListIsPublic(): void
    {
        $response = $this->get('/api/v2/devices');

        $response->assertSuccessful();
        $this->assertArrayHasKey('count', $response->json('data'));
        $this->assertArrayHasKey('items', $response->json('data'));
    }

    public function testListFiltersByPoweredCategory(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        // Cat2 (222) is seeded powered=1; Cat1 (111, used by the 'fixed' factory state) is
        // seeded powered=0.
        $poweredId = $this->createDevice($idevents, 'fixed', null, 1.5, 100, '', null, null, null, 222);
        $unpoweredId = $this->createDevice($idevents, 'fixed', null, 1.5, 100, '', null, null, null, 111);

        $response = $this->get('/api/v2/devices?powered=true&size=100');

        $response->assertSuccessful();
        $ids = array_column($response->json('data.items'), 'id');
        $this->assertContains($poweredId, $ids);
        $this->assertNotContains($unpoweredId, $ids);
    }

    public function testListFiltersByBrand(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        // createDevice() always sets brand='Test brand' - filter on that rather than posting a
        // custom brand directly (the helper doesn't expose a brand override, and mirroring its
        // proven request shape avoids duplicating the whole device-creation payload here).
        $iddevices = $this->createDevice($idevents, 'fixed', null, 1.5, 100, '', null, null, null, 111);

        $response = $this->get('/api/v2/devices?brand=Test+brand');

        $response->assertSuccessful();
        $ids = array_column($response->json('data.items'), 'id');
        $this->assertContains($iddevices, $ids);
    }

    public function testListRespectsPagination(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        for ($i = 0; $i < 3; $i++) {
            $this->createDevice($idevents, 'fixed', null, 1.5, 100, '', null, null, null, 111);
        }

        $response = $this->get('/api/v2/devices?size=2&page=1&brand=');

        $response->assertSuccessful();
        $this->assertLessThanOrEqual(2, count($response->json('data.items')));
    }

    public function testListSortsByWhitelistedColumn(): void
    {
        // Two groups whose names sort deterministically; a shared unique token
        // lets the `group` filter isolate exactly these two devices so the exact
        // order can be asserted regardless of any other rows in the shared DB.
        $host = User::factory()->host()->create();
        $this->actingAs($host);
        $token = 'Zsorttoken'.Str::random(6);
        $groupA = $this->createGroup('AAA '.$token, 'https://therestartproject.org', 'London');
        $groupZ = $this->createGroup('ZZZ '.$token, 'https://therestartproject.org', 'London');
        $eventA = $this->createEvent($groupA, '+1 week', true, true);
        $eventZ = $this->createEvent($groupZ, '+1 week', true, true);
        // Category 111 is seeded powered=0 (unpowered).
        $deviceA = $this->createDevice($eventA, 'fixed', null, 1.5, 100, '', null, null, null, 111);
        $deviceZ = $this->createDevice($eventZ, 'fixed', null, 1.5, 100, '', null, null, null, 111);

        $asc = $this->get('/api/v2/devices?powered=false&size=100&group='.$token.'&sortBy=groupname&sortDesc=asc');
        $asc->assertSuccessful();
        $this->assertEquals([$deviceA, $deviceZ], array_column($asc->json('data.items'), 'id'));

        $desc = $this->get('/api/v2/devices?powered=false&size=100&group='.$token.'&sortBy=groupname&sortDesc=desc');
        $desc->assertSuccessful();
        $this->assertEquals([$deviceZ, $deviceA], array_column($desc->json('data.items'), 'id'));
    }

    public function testListIgnoresUnknownSortColumn(): void
    {
        // sortBy is whitelisted: an unknown / injection-shaped column must not
        // error or reach the query grammar raw - it falls back to the default
        // event-date sort and the request still succeeds.
        $response = $this->get('/api/v2/devices?size=5&sortBy='.urlencode('devices.brand`); drop table devices;--'));

        $response->assertSuccessful();
        $this->assertArrayHasKey('items', $response->json('data'));
    }

    public function testListAcceptsEveryWhitelistedSortColumn(): void
    {
        // Guards against a typo/wrong-table in any single whitelist mapping: a
        // column that doesn't exist would make MySQL error (500) here, even
        // with only one row present.
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);
        $this->createDevice($idevents, 'fixed', null, 1.5, 100, '', null, null, null, 111);

        $columns = ['event_date', 'event_start_utc', 'item_type', 'category', 'brand', 'groupname', 'repair_status', 'created_at', 'iddevices'];
        foreach ($columns as $column) {
            foreach (['asc', 'desc'] as $direction) {
                $this->get("/api/v2/devices?powered=false&size=20&sortBy={$column}&sortDesc={$direction}")
                    ->assertSuccessful();
            }
        }
    }

    public function testUnknownSortFallsBackToEventDateOrder(): void
    {
        // Prove the fallback actually orders by events.event_start_utc DESC
        // (newest event first), not "drops ORDER BY / picks a wrong column".
        // Two events with different start dates, isolated by a shared group
        // token so the exact order can be asserted.
        $host = User::factory()->host()->create();
        $this->actingAs($host);
        $token = 'Zdatetoken'.Str::random(6);
        $earlyGroup = $this->createGroup('Early '.$token, 'https://therestartproject.org', 'London');
        $lateGroup = $this->createGroup('Late '.$token, 'https://therestartproject.org', 'London');
        $earlyEvent = $this->createEvent($earlyGroup, '+1 week', true, true);
        $lateEvent = $this->createEvent($lateGroup, '+3 weeks', true, true);
        $earlyDevice = $this->createDevice($earlyEvent, 'fixed', null, 1.5, 100, '', null, null, null, 111);
        $lateDevice = $this->createDevice($lateEvent, 'fixed', null, 1.5, 100, '', null, null, null, 111);

        // Unknown sortBy -> default events.event_start_utc DESC -> later event first.
        $response = $this->get('/api/v2/devices?powered=false&size=100&group='.$token.'&sortBy=not_a_column&sortDesc=desc');
        $response->assertSuccessful();
        $this->assertEquals([$lateDevice, $earlyDevice], array_column($response->json('data.items'), 'id'));
    }

    private function twoEventsWithDevices(string $token): array
    {
        $host = User::factory()->host()->create();
        $this->actingAs($host);
        $groupA = $this->createGroup('Alpha '.$token, 'https://therestartproject.org', 'London');
        $groupB = $this->createGroup('Beta '.$token, 'https://therestartproject.org', 'London');

        $early = Party::factory()->moderated()->create([
            'group' => $groupA,
            'event_start_utc' => '2024-03-10T13:00:00+00:00',
            'event_end_utc' => '2024-03-10T15:00:00+00:00',
        ]);
        $late = Party::factory()->moderated()->create([
            'group' => $groupB,
            'event_start_utc' => '2024-05-20T13:00:00+00:00',
            'event_end_utc' => '2024-05-20T15:00:00+00:00',
        ]);

        $make = fn ($event, $category, $status) => Device::factory()->create([
            'category' => $category,
            'category_creation' => $category,
            'repair_status' => $status,
            'event' => $event->idevents,
        ])->iddevices;

        return [
            'powered' => $make($early, 222, Device::REPAIR_STATUS_FIXED),
            'unpowered' => $make($late, 111, Device::REPAIR_STATUS_ENDOFLIFE),
            'early' => $early,
            'late' => $late,
        ];
    }

    private function ids(string $query): array
    {
        $response = $this->get('/api/v2/devices?size=100&'.$query);
        $response->assertSuccessful();

        return array_column($response->json('data.items'), 'id');
    }

    public function testAbsentPoweredMeansBothPoweredAndUnpowered(): void
    {
        $token = 'Both'.Str::random(6);
        $d = $this->twoEventsWithDevices($token);

        $this->assertEqualsCanonicalizing([$d['powered'], $d['unpowered']], $this->ids('group='.$token));
        $this->assertEquals([$d['unpowered']], $this->ids('group='.$token.'&powered=false'));
        $this->assertEquals([$d['powered']], $this->ids('group='.$token.'&powered=true'));
    }

    public function testStatusAcceptsNamesAndNumbers(): void
    {
        $token = 'Status'.Str::random(6);
        $d = $this->twoEventsWithDevices($token);

        $this->assertEquals([$d['powered']], $this->ids('group='.$token.'&status='.urlencode(Device::REPAIR_STATUS_FIXED_STR)));
        $this->assertEquals([$d['unpowered']], $this->ids('group='.$token.'&status='.urlencode(Device::REPAIR_STATUS_ENDOFLIFE_STR)));
        $this->assertEquals([$d['unpowered']], $this->ids('group='.$token.'&status='.Device::REPAIR_STATUS_ENDOFLIFE));
    }

    public function testToDateIsInclusiveAndFromDateStartsTheDay(): void
    {
        $token = 'Dates'.Str::random(6);
        $d = $this->twoEventsWithDevices($token);

        $this->assertEquals([$d['powered']], $this->ids('group='.$token.'&to_date=2024-03-10'));
        $this->assertEquals([$d['unpowered']], $this->ids('group='.$token.'&from_date=2024-05-20'));
        $this->assertEqualsCanonicalizing([$d['powered'], $d['unpowered']], $this->ids('group='.$token.'&from_date=2024-03-10&to_date=2024-05-20'));
    }

    public function testDevicesOfDeletedEventsAreExcluded(): void
    {
        $token = 'Deleted'.Str::random(6);
        $d = $this->twoEventsWithDevices($token);
        $d['late']->delete();

        $this->assertEquals([$d['powered']], $this->ids('group='.$token));
    }

    public function testSortsByEventDate(): void
    {
        $token = 'EventSort'.Str::random(6);
        $d = $this->twoEventsWithDevices($token);

        $this->assertEquals([$d['powered'], $d['unpowered']], $this->ids('group='.$token.'&sortBy=event_date&sortDesc=asc'));
        $this->assertEquals([$d['unpowered'], $d['powered']], $this->ids('group='.$token.'&sortBy=event_date&sortDesc=desc'));
        // The default is the most recent event first.
        $this->assertEquals([$d['unpowered'], $d['powered']], $this->ids('group='.$token));
    }

    public function testItemsKeepTheirOwnCreatedAtAndCarryTheEventDate(): void
    {
        $token = 'Created'.Str::random(6);
        $d = $this->twoEventsWithDevices($token);
        $device = Device::find($d['powered']);
        $device->created_at = '2024-03-11 10:00:00';
        $device->save();

        $items = $this->get('/api/v2/devices?size=100&group='.$token.'&powered=true')->json('data.items');
        $this->assertEquals('2024-03-11', substr($items[0]['created_at'], 0, 10));
        $this->assertEquals('2024-03-10', substr($items[0]['event_date'], 0, 10));
    }
}

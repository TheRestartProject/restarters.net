<?php

namespace Tests\Feature\Fixometer;

use App\Device;
use App\Party;
use App\Role;
use Carbon\Carbon;
use Tests\TestCase;

class RepairRecordsApiTest extends TestCase
{
    private const POWERED_CATEGORY = 11;
    private const UNPOWERED_CATEGORY = 111;

    private $event1;
    private $event2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $idgroupsA = $this->createGroup('Alpha Group');
        $idgroupsB = $this->createGroup('Beta Group');

        $this->event1 = Party::factory()->moderated()->create([
            'group' => $idgroupsA,
            'event_start_utc' => '2024-03-10T13:00:00+00:00',
            'event_end_utc' => '2024-03-10T15:00:00+00:00',
        ]);

        $this->event2 = Party::factory()->moderated()->create([
            'group' => $idgroupsB,
            'event_start_utc' => '2024-05-20T13:00:00+00:00',
            'event_end_utc' => '2024-05-20T15:00:00+00:00',
        ]);

        $this->device('Laptop', self::POWERED_CATEGORY, Device::REPAIR_STATUS_FIXED, $this->event1);
        $this->device('Kettle', self::POWERED_CATEGORY, Device::REPAIR_STATUS_ENDOFLIFE, $this->event2);
        $this->device('Trousers', self::UNPOWERED_CATEGORY, Device::REPAIR_STATUS_REPAIRABLE, $this->event1);
    }

    private function device($itemType, $category, $status, $event): Device
    {
        return Device::factory()->create([
            'item_type' => $itemType,
            'category' => $category,
            'category_creation' => $category,
            'repair_status' => $status,
            'event' => $event->idevents,
        ]);
    }

    private function records(array $params): array
    {
        $response = $this->get('/api/devices/1/20?' . http_build_query($params));
        $response->assertSuccessful();

        return json_decode($response->getContent(), true);
    }

    private function itemTypes(array $params): array
    {
        return array_column($this->records($params)['items'], 'item_type');
    }

    public function testWithoutPoweredFilterReturnsPoweredAndUnpowered(): void
    {
        $json = $this->records([]);
        $this->assertEquals(3, $json['count']);
    }

    public function testPoweredFilter(): void
    {
        $this->assertEqualsCanonicalizing(['Laptop', 'Kettle'], $this->itemTypes(['powered' => 'true']));
        $this->assertEquals(['Trousers'], $this->itemTypes(['powered' => 'false']));
    }

    public function testSortsByTheRequestedColumn(): void
    {
        $this->assertEquals(['Kettle', 'Laptop', 'Trousers'], $this->itemTypes(['sortBy' => 'item_type', 'sortDesc' => 'ASC']));
        $this->assertEquals(['Trousers', 'Laptop', 'Kettle'], $this->itemTypes(['sortBy' => 'item_type', 'sortDesc' => 'DESC']));
    }

    public function testSortsByGroupName(): void
    {
        $this->assertEquals('Kettle', $this->itemTypes(['sortBy' => 'groupname', 'sortDesc' => 'DESC'])[0]);
    }

    public function testDefaultSortIsMostRecentEventFirst(): void
    {
        $this->assertEquals('Kettle', $this->itemTypes([])[0]);
    }

    public function testUnknownSortColumnFallsBackToDefault(): void
    {
        $this->assertEquals('Kettle', $this->itemTypes(['sortBy' => 'nonsense; DROP TABLE devices', 'sortDesc' => 'DESC'])[0]);
    }

    public function testFiltersByStatusName(): void
    {
        // The client sends the status strings used across the API.
        $this->assertEquals(['Laptop'], $this->itemTypes(['status' => Device::REPAIR_STATUS_FIXED_STR]));
        $this->assertEquals(['Kettle'], $this->itemTypes(['status' => Device::REPAIR_STATUS_ENDOFLIFE_STR]));
        $this->assertEquals(['Trousers'], $this->itemTypes(['status' => Device::REPAIR_STATUS_REPAIRABLE_STR]));
    }

    public function testToDateIncludesEventsOnThatDay(): void
    {
        $this->assertEqualsCanonicalizing(['Laptop', 'Trousers'], $this->itemTypes(['to_date' => '2024-03-10']));
        $this->assertEquals(['Kettle'], $this->itemTypes(['from_date' => '2024-05-20']));
    }

    public function testRecordsCarryTheirOwnDatesAndEventDate(): void
    {
        $device = Device::where('item_type', 'Laptop')->first();
        $device->created_at = Carbon::parse('2024-03-11 10:00:00');
        $device->save();

        $item = $this->records(['sortBy' => 'item_type', 'sortDesc' => 'ASC'])['items'][1];
        $this->assertEquals('Laptop', $item['item_type']);
        $this->assertEquals($device->iddevices, $item['id']);
        $this->assertEquals('2024-03-11', substr($item['created_at'], 0, 10));
        $this->assertEquals('2024-03-10', substr($item['event_date'], 0, 10));
    }
}

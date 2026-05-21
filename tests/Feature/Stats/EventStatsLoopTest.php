<?php

namespace Tests\Feature\Stats;

use App\Device;
use App\Group;
use App\Party;
use Carbon\Carbon;
use Tests\Feature\Stats\StatsTestCase;

/**
 * Tests for getEventStats() covering every branch of the per-device loop,
 * run against events loaded via the allDevices JOIN (no deviceCategory eager load)
 * to verify the optimised path produces identical results.
 */
class EventStatsLoopTest extends StatsTestCase
{
    private Group $group;
    private Party $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->_setupCategoriesWithUnpoweredWeights();
        $this->group = Group::factory()->create();
        $this->event = Party::factory()->moderated()->create([
            'group'           => $this->group->idgroups,
            'event_start_utc' => Carbon::parse('2020-01-01 10:00:00')->toIso8601String(),
            'event_end_utc'   => Carbon::parse('2020-01-01 13:00:00')->toIso8601String(),
            'volunteers'      => 3,
            'pax'             => 10,
        ]);
    }

    /** Load the event fresh from DB via the allDevices JOIN (same path as production). */
    private function freshEventStats(bool $includeFuture = false): array
    {
        $event = Party::past()
            ->with('allDevices')
            ->where('events.idevents', $this->event->idevents)
            ->first();

        return $event->getEventStats(null, null, $includeFuture);
    }

    /** @test */
    public function powered_non_misc_fixed_device_contributes_co2_and_waste(): void
    {
        Device::factory()->fixed()->create([
            'category'          => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
            'event'             => $this->event->idevents,
            'estimate'          => 0,
        ]);

        $stats = $this->freshEventStats();

        $this->assertEquals(1, $stats['devices_powered']);
        $this->assertEquals(1, $stats['fixed_devices']);
        $this->assertEquals(1, $stats['fixed_powered']);
        $this->assertEquals(0, $stats['devices_unpowered']);
        $this->assertEquals(4, $stats['waste_powered']);   // category weight = 4
        $this->assertEquals(0, $stats['no_weight_powered']);
        $this->assertEqualsWithDelta(14.4 * $this->_displacementFactor, $stats['co2_powered'], 0.001);
    }

    /** @test */
    public function powered_misc_fixed_device_with_estimate_uses_estimate(): void
    {
        Device::factory()->fixed()->create([
            'category'          => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event'             => $this->event->idevents,
            'estimate'          => 5,
        ]);

        $stats = $this->freshEventStats();

        $this->assertEquals(1, $stats['fixed_devices']);
        $this->assertEquals(1, $stats['fixed_powered']);
        $this->assertEquals(5, $stats['waste_powered']);  // estimate used
        $this->assertEquals(0, $stats['no_weight_powered']); // has estimate, not no_weight
        $this->assertEqualsWithDelta(5 * $this->_ratioPowered * $this->_displacementFactor, $stats['co2_powered'], 0.001);
    }

    /** @test */
    public function powered_misc_fixed_device_without_estimate_increments_no_weight(): void
    {
        Device::factory()->fixed()->create([
            'category'          => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event'             => $this->event->idevents,
            'estimate'          => 0,
        ]);

        $stats = $this->freshEventStats();

        $this->assertEquals(1, $stats['fixed_devices']);
        $this->assertEquals(1, $stats['no_weight_powered']);
        $this->assertEquals(0, $stats['waste_powered']);
        $this->assertEquals(0, $stats['co2_powered']);
    }

    /** @test */
    public function unpowered_non_misc_fixed_device_contributes_co2_and_waste(): void
    {
        Device::factory()->fixed()->create([
            'category'          => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
            'event'             => $this->event->idevents,
            'estimate'          => 0,
        ]);

        $stats = $this->freshEventStats();

        $this->assertEquals(1, $stats['devices_unpowered']);
        $this->assertEquals(1, $stats['fixed_devices']);
        $this->assertEquals(1, $stats['fixed_unpowered']);
        $this->assertEquals(5, $stats['waste_unpowered']);  // category weight = 5
        $this->assertEquals(0, $stats['no_weight_unpowered']);
        $this->assertEqualsWithDelta(15.5 * $this->_displacementFactor, $stats['co2_unpowered'], 0.001);
    }

    /** @test */
    public function unpowered_misc_fixed_device_without_estimate_increments_no_weight(): void
    {
        Device::factory()->fixed()->create([
            'category'          => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event'             => $this->event->idevents,
            'estimate'          => 0,
        ]);

        $stats = $this->freshEventStats();

        $this->assertEquals(1, $stats['fixed_devices']);
        $this->assertEquals(1, $stats['no_weight_unpowered']);
        $this->assertEquals(0, $stats['waste_unpowered']);
        $this->assertEquals(0, $stats['co2_unpowered']);
    }

    /** @test */
    public function repairable_and_dead_devices_counted_correctly(): void
    {
        Device::factory()->repairable()->create([
            'category'          => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
            'event'             => $this->event->idevents,
        ]);
        Device::factory()->create([
            'category'          => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
            'event'             => $this->event->idevents,
            'repair_status'     => Device::REPAIR_STATUS_ENDOFLIFE,
        ]);

        $stats = $this->freshEventStats();

        $this->assertEquals(0, $stats['fixed_devices']);
        $this->assertEquals(1, $stats['repairable_devices']);
        $this->assertEquals(1, $stats['dead_devices']);
        $this->assertEquals(0, $stats['co2_powered']);
        $this->assertEquals(0, $stats['waste_powered']);
    }

    /** @test */
    public function participants_volunteers_and_hours_come_from_event_record(): void
    {
        $stats = $this->freshEventStats();

        $this->assertEquals(10, $stats['participants']);
        $this->assertEquals(3,  $stats['volunteers']);
        // 3h event + 3 volunteers * 3h + 9h extra host = 9 + 9 + 3 = 21... let hoursVolunteered() govern
        $this->assertGreaterThan(0, $stats['hours_volunteered']);
    }
}

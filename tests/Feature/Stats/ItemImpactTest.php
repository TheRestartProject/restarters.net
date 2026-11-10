<?php

namespace Tests\Feature\Stats;

use App\Device;
use App\Helpers\LcaStats;
use App\Party;
use App\Services\ItemImpact;
use App\Services\TypicalMiscWeights;
use Carbon\Carbon;

/**
 * The one rule for an item's waste and CO2e (RES-2063). Categories from StatsTestCase:
 * powered non-misc 4 kg / 14.4 kg CO2e, unpowered non-misc 5 kg / 15.5 kg CO2e, Misc 0 / 0.
 */
class ItemImpactTest extends StatsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->_setupCategoriesWithUnpoweredWeights();
    }

    public function test_an_entered_weight_counts_between_a_hundredth_and_ten_times_the_category_weight(): void
    {
        $this->assertTrue(ItemImpact::counts(0.04, 4, true));
        $this->assertFalse(ItemImpact::counts(0.039, 4, true));
        $this->assertTrue(ItemImpact::counts(40, 4, true));
        $this->assertFalse(ItemImpact::counts(40.001, 4, true));
        $this->assertTrue(ItemImpact::counts(0.6, 0.06, false));
        $this->assertTrue(ItemImpact::counts(12.8, 1.28, true));
        $this->assertFalse(ItemImpact::counts(null, 4, true));
        $this->assertFalse(ItemImpact::counts(0, 4, true));
    }

    public function test_a_misc_weight_counts_up_to_twenty_times_the_typical_misc_weight(): void
    {
        $this->_setTypicalMiscWeights(2, 0.75);

        $this->assertTrue(ItemImpact::counts(0.001, 0, true));
        $this->assertTrue(ItemImpact::counts(40, 0, true));
        $this->assertFalse(ItemImpact::counts(40.001, 0, true));
        $this->assertTrue(ItemImpact::counts(15, 0, false));
        $this->assertFalse(ItemImpact::counts(15.5, 0, false));
        $this->assertFalse(ItemImpact::counts(null, 0, false));
    }

    public function test_any_misc_weight_counts_when_there_is_no_typical_weight_yet(): void
    {
        $this->_setTypicalMiscWeights(0, 0);

        $this->assertTrue(ItemImpact::counts(999.999, 0, true));
        $this->assertEquals([0.001, null], ItemImpact::countedRange(0, true));
    }

    public function test_counted_range_and_typical_weight(): void
    {
        $this->_setTypicalMiscWeights(2, 0.75);

        $this->assertEquals([0.04, 40.0], ItemImpact::countedRange(4, true));
        $this->assertEquals([0.001, 40.0], ItemImpact::countedRange(0, true));
        $this->assertEquals([0.001, 15.0], ItemImpact::countedRange(0, false));
        $this->assertEquals(4.0, ItemImpact::typicalWeight(4, true));
        $this->assertEquals(0.75, ItemImpact::typicalWeight(0, false));
    }

    public function test_waste_is_the_entered_weight_if_it_counts_else_the_typical_weight(): void
    {
        $this->_setTypicalMiscWeights(2, 0.75);

        $this->assertEquals(3.5, ItemImpact::waste(3.5, 4, true));
        $this->assertEquals(4, ItemImpact::waste(null, 4, true));
        $this->assertEquals(4, ItemImpact::waste(400, 4, true));
        $this->assertEquals(2, ItemImpact::waste(null, 0, true));
        $this->assertEquals(0.75, ItemImpact::waste(null, 0, false));
        $this->assertEquals(0.75, ItemImpact::waste(600, 0, false));
        $this->assertEquals(6, ItemImpact::waste(6, 0, false));
    }

    public function test_co2_uses_the_category_co2_per_kg_for_an_entered_weight(): void
    {
        $this->_setTypicalMiscWeights(2, 0.75);
        $eR = LcaStats::getEmissionRatioPowered();
        $uR = LcaStats::getEmissionRatioUnpowered();

        // 15.5 kg CO2e for a 5 kg item is 3.1 kg CO2e per kg.
        $this->assertEqualsWithDelta(31, ItemImpact::co2(10, 5, 15.5, false), 0.0001);
        $this->assertEqualsWithDelta(15.5, ItemImpact::co2(null, 5, 15.5, false), 0.0001);
        $this->assertEqualsWithDelta(15.5, ItemImpact::co2(500, 5, 15.5, false), 0.0001);
        $this->assertEqualsWithDelta(3 * 3.6, ItemImpact::co2(3, 4, 14.4, true), 0.0001);

        // Misc uses the general ratio, and the typical weight when blank.
        $this->assertEqualsWithDelta(1.5 * $eR, ItemImpact::co2(1.5, 0, 0, true), 0.0001);
        $this->assertEqualsWithDelta(2 * $eR, ItemImpact::co2(null, 0, 0, true), 0.0001);
        $this->assertEqualsWithDelta(0.75 * $uR, ItemImpact::co2(null, 0, 0, false), 0.0001);
    }

    public function test_the_typical_misc_weight_is_the_median_of_the_last_twelve_months(): void
    {
        $recent = Party::factory()->create(['event_start_utc' => Carbon::now()->subMonths(2)->toIso8601String(), 'event_end_utc' => Carbon::now()->subMonths(2)->addHour()->toIso8601String()]);
        $old = Party::factory()->create(['event_start_utc' => Carbon::now()->subMonths(13)->toIso8601String(), 'event_end_utc' => Carbon::now()->subMonths(13)->addHour()->toIso8601String()]);

        foreach ([1, 2, 3, 100] as $kg) {
            Device::factory()->create(['event' => $recent->idevents, 'category' => $this->_idPoweredMisc, 'category_creation' => $this->_idPoweredMisc, 'estimate' => $kg]);
        }
        Device::factory()->create(['event' => $recent->idevents, 'category' => $this->_idPoweredMisc, 'category_creation' => $this->_idPoweredMisc, 'estimate' => null]);
        Device::factory()->create(['event' => $old->idevents, 'category' => $this->_idPoweredMisc, 'category_creation' => $this->_idPoweredMisc, 'estimate' => 500]);
        Device::factory()->create(['event' => $recent->idevents, 'category' => $this->_idUnpoweredMisc, 'category_creation' => $this->_idUnpoweredMisc, 'estimate' => 0.5]);
        // A weight in a category with its own weight does not count towards the Misc median.
        Device::factory()->create(['event' => $recent->idevents, 'category' => $this->_idPoweredNonMisc, 'category_creation' => $this->_idPoweredNonMisc, 'estimate' => 50]);

        $typical = new TypicalMiscWeights();
        $this->assertEquals(['powered' => 2.5, 'unpowered' => 0.5], $typical->calculate());
        $this->assertEquals(2.5, $typical->get(true));
    }

    public function test_sql_and_php_agree(): void
    {
        $this->_setTypicalMiscWeights(2, 0.75);
        $event = Party::factory()->create();
        $cases = [
            [$this->_idPoweredNonMisc, null], [$this->_idPoweredNonMisc, 3], [$this->_idPoweredNonMisc, 300],
            [$this->_idPoweredMisc, null], [$this->_idPoweredMisc, 1.5], [$this->_idPoweredMisc, 999.999],
            [$this->_idUnpoweredNonMisc, null], [$this->_idUnpoweredNonMisc, 0.01], [$this->_idUnpoweredNonMisc, 0.05], [$this->_idUnpoweredNonMisc, 12],
            [$this->_idUnpoweredMisc, null], [$this->_idUnpoweredMisc, 0.4], [$this->_idUnpoweredMisc, 16],
        ];
        $php = ['waste' => 0, 'co2' => 0];
        foreach ($cases as [$category, $kg]) {
            $device = Device::factory()->fixed()->create(['event' => $event->idevents, 'category' => $category, 'category_creation' => $category, 'estimate' => $kg]);
            $php['waste'] += $device->eWasteDiverted() + $device->uWasteDiverted();
            $php['co2'] += $device->eCo2Diverted(LcaStats::getEmissionRatioPowered(), LcaStats::getDisplacementFactor())
                + $device->uCo2Diverted(LcaStats::getEmissionRatioUnpowered(), LcaStats::getDisplacementFactor());
        }

        $sql = LcaStats::getWasteStats()[0];
        $this->assertEqualsWithDelta($php['waste'], $sql->powered_waste + $sql->unpowered_waste, 0.001);
        $this->assertEqualsWithDelta($php['co2'], $sql->powered_footprint + $sql->unpowered_footprint, 0.001);

        $stats = $event->fresh()->getEventStats();
        $this->assertEqualsWithDelta($php['waste'], $stats['waste_total'], 0.001);
        $this->assertEqualsWithDelta($php['co2'], $stats['co2_total'], 0.001);
    }

    public function test_php_and_sql_agree_at_the_band_edge_for_a_category_weight_stored_as_a_float(): void
    {
        $this->_setTypicalMiscWeights(2, 0.75);
        \App\Category::factory()->create(['idcategories' => 10, 'revision' => 1, 'name' => 'Coffee maker', 'powered' => 1, 'weight' => 1.28, 'footprint' => 17.5]);
        $event = Party::factory()->create();
        $device = Device::factory()->fixed()->create(['event' => $event->idevents, 'category' => 10, 'category_creation' => 10, 'estimate' => 12.8]);

        // 12.8 kg is exactly 10 times 1.28 kg, so it counts, in PHP and in SQL alike.
        $this->assertEquals(12.8, $device->eWasteDiverted());
        $this->assertEqualsWithDelta(12.8, LcaStats::getWasteStats()[0]->powered_waste, 0.0001);
    }

    public function test_an_item_without_a_category_prevents_nothing(): void
    {
        $device = new Device(['category' => 99999, 'repair_status' => Device::REPAIR_STATUS_FIXED]);

        $this->assertEquals(0, $device->eWasteDiverted());
        $this->assertEquals(0, $device->uWasteDiverted());
        $this->assertEquals(0, $device->eCo2Diverted(1, 0.5));
        $this->assertEquals(0, $device->uCo2Diverted(1, 0.5));
    }

    public function test_a_missing_typical_misc_weight_is_only_held_for_an_hour(): void
    {
        // Powered Misc has weights, unpowered Misc has none yet: the 0 for unpowered must not be held for a day.
        $event = Party::factory()->create(['event_start_utc' => Carbon::now()->subDay()->toIso8601String(), 'event_end_utc' => Carbon::now()->subDay()->addHour()->toIso8601String()]);
        Device::factory()->create(['event' => $event->idevents, 'category' => $this->_idPoweredMisc, 'category_creation' => $this->_idPoweredMisc, 'estimate' => 3]);
        $this->assertEquals(['powered' => 3, 'unpowered' => 0], [
            'powered' => (new TypicalMiscWeights())->get(true),
            'unpowered' => (new TypicalMiscWeights())->get(false),
        ]);

        Device::factory()->create(['event' => $event->idevents, 'category' => $this->_idUnpoweredMisc, 'category_creation' => $this->_idUnpoweredMisc, 'estimate' => 0.5]);

        try {
            Carbon::setTestNow(Carbon::now()->addMinutes(30));
            $this->assertEquals(0, (new TypicalMiscWeights())->get(false));
            Carbon::setTestNow(Carbon::now()->addMinutes(31));
            $this->assertEquals(0.5, (new TypicalMiscWeights())->get(false));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_php_and_sql_agree_at_the_lower_edge_of_the_band(): void
    {
        $this->_setTypicalMiscWeights(2, 0.75);
        \App\Category::factory()->create(['idcategories' => 11, 'revision' => 1, 'name' => 'Ring', 'powered' => 0, 'weight' => 0.1, 'footprint' => 5]);
        $event = Party::factory()->create();
        $device = Device::factory()->fixed()->create(['event' => $event->idevents, 'category' => 11, 'category_creation' => 11, 'estimate' => 0.001]);

        // 0.001 kg is exactly a hundredth of 0.1 kg, so it counts, in PHP and in SQL alike.
        $this->assertEquals(0.001, $device->uWasteDiverted());
        $this->assertEqualsWithDelta(0.001, LcaStats::getWasteStats()[0]->unpowered_waste, 0.00001);
    }

    public function test_a_category_whose_weight_rounds_to_nothing_is_misc_everywhere(): void
    {
        \App\Category::factory()->create(['idcategories' => 12, 'revision' => 1, 'name' => 'Feather', 'powered' => 1, 'weight' => 0.0004, 'footprint' => 0]);
        $event = Party::factory()->create(['event_start_utc' => Carbon::now()->subDay()->toIso8601String(), 'event_end_utc' => Carbon::now()->subDay()->addHour()->toIso8601String()]);
        Device::factory()->create(['event' => $event->idevents, 'category' => 12, 'category_creation' => 12, 'estimate' => 4]);

        $this->assertEquals(4, (new TypicalMiscWeights())->calculate()['powered']);
    }
}

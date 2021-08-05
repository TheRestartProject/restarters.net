<?php

namespace Tests\Feature\Stats;

use App\Device;
use App\DeviceBarrier;
use App\Category;
use App\Helpers\LcaStats;

use DB;
use Tests\TestCase;

class StatsTestCase extends TestCase
{
    protected $_displacementFactor;
    protected $_idMiscUnpowered;
    protected $_idMiscPowered;
    protected $_ratioUnpowered;

    protected function setUp(): void
    {
        parent::setUp();
        $this->_displacementFactor = env('DISPLACEMENT_VALUE');
        $this->_ratioUnpowered = env('UNPOWERED_EMISSION_RATIO');
        $this->_idMiscPowered = env('MISC_CATEGORY_ID_POWERED');
        $this->_idMiscUnpowered = env('MISC_CATEGORY_ID_UNPOWERED');
    }

    protected function _ratioPowered() {
        return LcaStats::getEmissionRatioPowered();
    }

    protected function _setupCategoriesWithUnpoweredWeights()
    {
        DB::statement("SET foreign_key_checks=0");
        Category::truncate();
        DB::statement("SET foreign_key_checks=1");
        factory(Category::class)->create([
            'idcategories' => 4,
            'revision' => 1,
            'name' => 'powered non-misc',
            'powered' => 1,
            'weight' => 4,
            'footprint' => 14.4,
        ]);
        factory(Category::class)->create([
            'idcategories' => $this->_idMiscPowered,
            'revision' => 1,
            'name' => 'powered misc',
            'powered' => 1,
            'weight' => 0,
            'footprint' => 0,
        ]);
        factory(Category::class)->create([
            'idcategories' => 5,
            'revision' => 1,
            'name' => 'unpowered non-misc',
            'powered' => 0,
            'weight' => 5,
            'footprint' => 15.5,
        ]);
        factory(Category::class)->create([
            'idcategories' => $this->_idMiscUnpowered,
            'revision' => 1,
            'name' => 'unpowered misc',
            'powered' => 0,
            'weight' => 0,
            'footprint' => 0,
        ]);
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
    }
}

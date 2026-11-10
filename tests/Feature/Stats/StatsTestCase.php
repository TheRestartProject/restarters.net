<?php

namespace Tests\Feature\Stats;

use App\Category;
use App\Device;
use App\DeviceBarrier;
use App\Helpers\LcaStats;
use DB;
use Tests\TestCase;

class StatsTestCase extends TestCase
{
    protected $_displacementFactor;
    protected $_ratioUnpowered;
    protected $_ratioPowered;
    protected $_idUnpoweredMisc;
    protected $_idPoweredMisc;
    protected $_idUnpoweredNonMisc;
    protected $_idPoweredNonMisc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->_displacementFactor = env('DISPLACEMENT_VALUE');
        $this->_ratioUnpowered = env('EMISSION_RATIO_UNPOWERED');
        $this->_ratioPowered = env('EMISSION_RATIO_POWERED');
        $this->_idPoweredMisc = env('MISC_CATEGORY_ID_POWERED');
        $this->_idUnpoweredMisc = env('MISC_CATEGORY_ID_UNPOWERED');
        $this->_idPoweredNonMisc = 4;
        $this->_idUnpoweredNonMisc = 5;
    }

    /**
     * Fix the typical Misc weights (normally the median of the last 12 months' Misc weights).
     */
    protected function _setTypicalMiscWeights(float $powered, float $unpowered): void
    {
        $this->app->instance(\App\Services\TypicalMiscWeights::class, new class($powered, $unpowered) extends \App\Services\TypicalMiscWeights {
            public function __construct(private float $p, private float $u) {}

            public function get(bool $powered): float
            {
                return $powered ? $this->p : $this->u;
            }
        });
    }

    protected function _setupCategoriesWithUnpoweredWeights()
    {
        DB::statement('SET foreign_key_checks=0');
        Category::truncate();
        DB::statement('SET foreign_key_checks=1');
        Category::factory()->create([
            'idcategories' => $this->_idPoweredNonMisc,
            'revision' => 1,
            'name' => 'powered non-misc',
            'powered' => 1,
            'weight' => 4,
            'footprint' => 14.4,
        ]);
        Category::factory()->create([
            'idcategories' => $this->_idPoweredMisc,
            'revision' => 1,
            'name' => 'powered misc',
            'powered' => 1,
            'weight' => 0,
            'footprint' => 0,
        ]);
        Category::factory()->create([
            'idcategories' => $this->_idUnpoweredNonMisc,
            'revision' => 1,
            'name' => 'unpowered non-misc',
            'powered' => 0,
            'weight' => 5,
            'footprint' => 15.5,
        ]);
        Category::factory()->create([
            'idcategories' => $this->_idUnpoweredMisc,
            'revision' => 1,
            'name' => 'unpowered misc',
            'powered' => 0,
            'weight' => 0,
            'footprint' => 0,
        ]);
        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        DeviceBarrier::truncate();
        DB::statement('SET foreign_key_checks=1');
    }
}

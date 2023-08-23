<?php

namespace Tests\Unit;

use App\Category;
use App\Device;
use App\Group;
use App\Helpers\Fixometer;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DeviceItemTypesTest extends TestCase
{
    public function testItemTypesDuplicates() {
        $cat1 = Category::factory()->create([
            'idcategories' => 444,
            'revision' => 1,
            'name' => 'Flat screen 22-24"',
            'powered' => 1,
        ]);

        $cat2 = Category::factory()->create([
            'idcategories' => 555,
            'revision' => 1,
            'name' => 'Flat screen 32-37"',
            'powered' => 1,
        ]);

        $dev1 = Device::factory()->fixed()->create([
            'category' => $cat1,
            'item_type' => 'flatscreen LCD'
        ]);

        $dev2 = Device::factory()->fixed()->create([
            'category' => $cat2,
            'item_type' => 'flatscreen LCD'
        ]);

        $this->assertEquals(1, count(Device::getItemTypes()));
    }
}

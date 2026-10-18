<?php

namespace Tests\Feature\Stats;

use App\Device;
use App\Group;
use App\Party;

class MostRepairedTest extends StatsTestCase
{
    private function fixedDevice(Party $event, int $category): void
    {
        Device::factory()->create([
            'event' => $event->idevents,
            'category' => $category,
            'category_creation' => $category,
            'repair_status' => env('DEVICE_FIXED'),
        ]);
    }

    public function testMostRepairedItemsIncludeUnpoweredButNotMisc(): void
    {
        $this->_setupCategoriesWithUnpoweredWeights();

        $group = Group::factory()->create();
        $event = Party::factory()->create(['group' => $group->idgroups]);

        $this->fixedDevice($event, $this->_idPoweredNonMisc);
        $this->fixedDevice($event, $this->_idUnpoweredNonMisc);
        $this->fixedDevice($event, $this->_idUnpoweredNonMisc);
        $this->fixedDevice($event, $this->_idPoweredMisc);
        $this->fixedDevice($event, $this->_idUnpoweredMisc);

        $top = (new Device)->findMostSeen(1, null, $group->idgroups);

        $this->assertEquals(
            [['unpowered non-misc', 2], ['powered non-misc', 1]],
            array_map(fn ($row) => [$row->name, $row->counter], $top)
        );
    }
}

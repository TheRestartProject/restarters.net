<?php

namespace Tests\Feature\Fixometer;

use App\Party;
use App\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HeadlineStatsTest extends TestCase
{
    public function testCountsEventsHeld(): void
    {
        Cache::flush();
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $idgroups = $this->createGroup('Stats Group');

        // Two past events count; a future one and a deleted one don't.
        foreach (['-2 months', '-1 month'] as $when) {
            Party::factory()->moderated()->create([
                'group' => $idgroups,
                'event_start_utc' => Carbon::parse($when)->toIso8601String(),
                'event_end_utc' => Carbon::parse($when)->addHours(2)->toIso8601String(),
            ]);
        }

        Party::factory()->moderated()->create([
            'group' => $idgroups,
            'event_start_utc' => Carbon::parse('+1 month')->toIso8601String(),
            'event_end_utc' => Carbon::parse('+1 month')->addHours(2)->toIso8601String(),
        ]);

        Party::factory()->moderated()->create([
            'group' => $idgroups,
            'event_start_utc' => Carbon::parse('-3 months')->toIso8601String(),
            'event_end_utc' => Carbon::parse('-3 months')->addHours(2)->toIso8601String(),
        ])->delete();

        $json = $this->get('/api/homepage_data')->assertSuccessful()->json();
        $this->assertEquals(2, $json['events']);
    }
}

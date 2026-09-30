<?php

namespace Tests\Feature\Events;

use App\Device;
use App\Group;
use App\Party;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExportDevicesQueryCountTest extends TestCase
{
    private function addEvents(int $count, bool $approved = true): void
    {
        $group = Group::factory()->create(['approved' => $approved]);

        for ($i = 0; $i < $count; $i++) {
            $event = Party::factory()->create([
                'group' => $group->idgroups,
                'approved' => $approved,
                'event_start_utc' => '2024-01-0' . ($i + 1) . 'T10:00:00+00:00',
                'event_end_utc' => '2024-01-0' . ($i + 1) . 'T12:00:00+00:00',
            ]);

            Device::factory()->fixed()->count(2)->create([
                'category' => 11,
                'category_creation' => 11,
                'event' => $event->idevents,
            ]);
        }
    }

    private function exportQueryCount(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/export/devices')->assertSuccessful();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function exportedRows(): array
    {
        $response = $this->get('/export/devices');
        $header = $response->headers->get('content-disposition');
        $filename = storage_path('app/exports') . '/' . substr($header, strpos($header, 'filename=') + 9);

        $rows = array_map('str_getcsv', file($filename));
        array_shift($rows);

        return $rows;
    }

    public function testQueryCountDoesNotGrowWithEvents(): void
    {
        $this->actingAs(User::factory()->restarter()->create());

        $this->addEvents(2);
        $this->exportQueryCount();
        $for2 = $this->exportQueryCount();

        $this->addEvents(6);
        $for8 = $this->exportQueryCount();

        $this->assertLessThanOrEqual($for2 + 2, $for8, "2 events: $for2 queries, 8 events: $for8 queries");
    }

    public function testOrdinaryUserOnlyGetsApprovedEvents(): void
    {
        $this->actingAs(User::factory()->restarter()->create());

        $this->addEvents(2);
        $this->addEvents(1, false);

        $this->assertCount(4, $this->exportedRows());
    }

    public function testAdministratorGetsEveryEvent(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->addEvents(2);
        $this->addEvents(1, false);

        $this->assertCount(6, $this->exportedRows());
    }

    public function testDeletedEventsAreLeftOut(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->addEvents(2);
        Party::first()->delete();

        $this->assertCount(2, $this->exportedRows());
    }
}

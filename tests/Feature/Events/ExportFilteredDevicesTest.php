<?php

namespace Tests\Feature\Events;

use App\Device;
use App\Group;
use App\Party;
use App\User;
use Tests\TestCase;

class ExportFilteredDevicesTest extends TestCase
{
    private function addEvent(string $date, bool $approved, array $device): void
    {
        $group = Group::factory()->create(['approved' => $approved]);
        $event = Party::factory()->create([
            'group' => $group->idgroups,
            'approved' => $approved,
            'event_start_utc' => $date . 'T10:00:00+00:00',
            'event_end_utc' => $date . 'T12:00:00+00:00',
        ]);

        Device::factory()->fixed()->create($device + [
            'category' => 11,
            'category_creation' => 11,
            'event' => $event->idevents,
        ]);
    }

    private function rows(string $url): array
    {
        $response = $this->get($url);
        $response->assertSuccessful();
        $header = $response->headers->get('content-disposition');
        // Each export is written to its own unique file; the friendly name is only the download name.
        $filename = $response->baseResponse->getFile()->getPathname();

        $lines = file($filename);
        $columns = count(str_getcsv(array_shift($lines)));
        $rows = array_map('str_getcsv', $lines);

        return [$rows, $header, $columns];
    }

    public function testFilteredExportOnlyContainsMatchingRecords(): void
    {
        $this->actingAs(User::factory()->restarter()->create());

        $this->addEvent('2024-01-01', true, ['brand' => 'Zorblax']);
        $this->addEvent('2024-02-01', true, ['brand' => 'Plinth']);
        $this->addEvent('2024-03-01', true, ['brand' => 'Zorblax']);

        [$rows, $header, $columns] = $this->rows('/export/devices?brand=Zorblax');
        $this->assertCount(2, $rows);
        $this->assertEquals(['Zorblax', 'Zorblax'], array_column($rows, 2));
        $this->assertStringContainsString('repair-data-filtered.csv', $header);

        // Same columns as the full export.
        [, , $allColumns] = $this->rows('/export/devices');
        $this->assertEquals($allColumns, $columns);

        // Dates are inclusive, and combine with other filters.
        [$rows] = $this->rows('/export/devices?brand=Zorblax&from_date=2024-02-01&to_date=2024-03-01');
        $this->assertCount(1, $rows);

        [$rows] = $this->rows('/export/devices?from_date=2024-02-01&to_date=2024-02-01');
        $this->assertEquals(['Plinth'], array_column($rows, 2));
    }

    public function testFilteredExportCanSelectUnpoweredOnly(): void
    {
        $this->actingAs(User::factory()->restarter()->create());

        $unpowered = \DB::table('categories')->where('powered', 0)->value('idcategories');
        $powered = \DB::table('categories')->where('powered', 1)->value('idcategories');

        $this->addEvent('2024-01-01', true, ['brand' => 'Unplugged', 'category' => $unpowered, 'category_creation' => $unpowered]);
        $this->addEvent('2024-01-02', true, ['brand' => 'Plugged', 'category' => $powered, 'category_creation' => $powered]);

        [$rows] = $this->rows('/export/devices?powered=false');
        $this->assertEquals(['Unplugged'], array_column($rows, 2));

        [$rows] = $this->rows('/export/devices?powered=true');
        $this->assertEquals(['Plugged'], array_column($rows, 2));
    }

    public function testFilteredExportKeepsVisibilityRules(): void
    {
        $this->actingAs(User::factory()->restarter()->create());

        $this->addEvent('2024-01-01', true, ['brand' => 'Zorblax']);
        $this->addEvent('2024-01-02', false, ['brand' => 'Zorblax']);

        [$rows] = $this->rows('/export/devices?brand=Zorblax');
        $this->assertCount(1, $rows);
    }

    public function testExportWithoutFiltersIsTheFullExport(): void
    {
        $this->actingAs(User::factory()->restarter()->create());

        $this->addEvent('2024-01-01', true, ['brand' => 'Zorblax']);
        $this->addEvent('2024-02-01', true, ['brand' => 'Plinth']);

        [$rows, $header] = $this->rows('/export/devices');
        $this->assertCount(2, $rows);
        $this->assertStringNotContainsString('filtered', $header);
    }

    public function testFilteredExportRejectsBadDates(): void
    {
        // TestCase disables exception handling globally; we want the rendered 422 here.
        $this->withExceptionHandling();
        $this->get('/export/devices?from_date=banana', ['Accept' => 'application/json'])
            ->assertStatus(422);
    }
}

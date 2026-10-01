<?php

namespace Tests\Feature\Fixometer;

use App\Device;
use App\Group;
use App\Party;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RepairDataFileTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = public_path('exports/repair-data.csv');
        @unlink($this->file);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    private function addDevice(bool $approved = true, string $itemType = 'Kettle'): void
    {
        $group = Group::factory()->create(['approved' => $approved]);
        $event = Party::factory()->create(['group' => $group->idgroups, 'approved' => $approved]);

        Device::factory()->fixed()->create([
            'category' => 11,
            'category_creation' => 11,
            'event' => $event->idevents,
            'item_type' => $itemType,
        ]);
    }

    private function build(): string
    {
        Artisan::call('export:repair-data');

        return Artisan::output();
    }

    private function ageFile(): void
    {
        touch($this->file, time() - 2 * 86400);
        clearstatcache();
    }

    public function testBuildsTheFileWithWhatAnyoneCanSee(): void
    {
        $this->addDevice(true, 'Visible kettle');
        $this->addDevice(false, 'Hidden toaster');

        $this->build();

        $csv = file_get_contents($this->file);
        $this->assertStringContainsString('Visible kettle', $csv);
        $this->assertStringNotContainsString('Hidden toaster', $csv);
    }

    public function testRebuildsOnlyOnceADay(): void
    {
        $this->addDevice(true, 'First kettle');
        $this->build();

        $this->addDevice(true, 'Second kettle');
        $this->build();

        $this->assertStringNotContainsString('Second kettle', file_get_contents($this->file));
    }

    public function testRebuildsADayLaterWhenThereAreNewRepairs(): void
    {
        $this->addDevice(true, 'First kettle');
        $this->build();
        $this->ageFile();

        $this->addDevice(true, 'Second kettle');
        $this->build();

        $this->assertStringContainsString('Second kettle', file_get_contents($this->file));
    }

    public function testDoesNotRebuildWhenNothingHasChanged(): void
    {
        $this->addDevice(true, 'First kettle');
        $this->build();
        $this->ageFile();
        $before = filemtime($this->file);

        $this->assertStringContainsString('unchanged', $this->build());

        clearstatcache();
        $this->assertGreaterThan($before, filemtime($this->file), 'Should be marked as checked so it is not rechecked every hour');
    }

    public function testFixometerDownloadsTheBuiltFile(): void
    {
        $this->addDevice();
        $this->build();

        $this->loginAsTestUser();
        $this->get('/fixometer')->assertSee('/exports/repair-data.csv', false);
    }

    public function testFixometerFallsBackToALiveExportBeforeTheFileIsBuilt(): void
    {
        $this->loginAsTestUser();
        $this->get('/fixometer')
            ->assertDontSee('/exports/repair-data.csv', false)
            ->assertSee('/export/devices', false);
    }
}

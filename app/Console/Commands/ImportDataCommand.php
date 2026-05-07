<?php

namespace App\Console\Commands;

use App\Services\DataImportService;
use Illuminate\Console\Command;

class ImportDataCommand extends Command
{
    protected $signature = 'import:data
        {itemsCsv? : Path to items CSV}
        {eventsCsv? : Path to events CSV for full import mode}
        {--group-id= : Group ID}
        {--event-id= : Existing event ID for item-only import mode}';

    protected $description = 'Import Restarters events/items, or items for one existing event';

    public function handle(DataImportService $service): int
    {
        $groupId = $this->option('group-id');

        if (!$groupId) {
            $this->error('--group-id is required');
            return self::FAILURE;
        }

        $eventId = $this->option('event-id');
        $itemsCsv = $this->argument('itemsCsv');
        $eventsCsv = $this->argument('eventsCsv');

        try {
            if ($eventId) {
                if (!$itemsCsv) {
                    $this->error('In item-only mode, provide itemsCsv');
                    return self::FAILURE;
                }

                $report = $service->importItemsForExistingEvent($itemsCsv, (int) $groupId, (int) $eventId);
            } else {
                if (!$itemsCsv || !$eventsCsv) {
                    $this->error('In full mode, provide itemsCsv and eventsCsv');
                    return self::FAILURE;
                }

                $report = $service->importAllEvents($eventsCsv, $itemsCsv, (int) $groupId);
            }

            $this->renderReport($report);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    protected function renderReport(array $report): void
    {
        $this->newLine();
        $this->line('=== Import report ===');
        $this->line('Events created: '.$report['events']['created']);
        $this->line('Events existing: '.$report['events']['existing']);
        $this->line('Events failed: '.$report['events']['failed']);
        $this->newLine();

        $this->line('Items total:');
        $this->line(' Items created: '.$report['items_total']['created']);
        $this->line(' Items skipped (category): '.$report['items_total']['skipped_unknown_category']);
        $this->line(' Items failed to create: '.$report['items_total']['failed']);
        $this->newLine();

        foreach ($report['items_per_event'] as $eventKey => $stats) {
            $eventId = $report['event_ids'][$eventKey] ?? 'unknown';
            $this->line("Event: {$eventKey} (id: {$eventId})");
            $this->line(" Items created: {$stats['created']}");
            $this->line(" Items skipped (category): {$stats['skipped_unknown_category']}");
            $this->line(" Items failed: {$stats['failed']}");
            $this->newLine();
        }

        $this->line('Errors:');
        if (empty($report['errors'])) {
            $this->line(' None');
            return;
        }

        foreach ($report['errors'] as $err) {
            $ctx = collect($err['context'] ?? [])
                ->map(fn ($v, $k) => "{$k}={$v}")
                ->implode(', ');

            $this->line(" - [{$err['type']}] {$err['message']}".($ctx ? " ({$ctx})" : ''));
        }
    }
}

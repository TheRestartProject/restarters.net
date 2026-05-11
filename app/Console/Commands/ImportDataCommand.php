<?php

namespace App\Console\Commands;

use App\Services\DataImportService;
use Illuminate\Console\Command;

class ImportDataCommand extends Command
{
    protected $signature = 'import:data
        {itemsCsv : Path to items CSV}
        {eventsCsv? : Path to events CSV for full import mode}
        {--group-id= : Group ID}
        {--event-id= : Existing event ID for item-only import mode}
        {--api-token= : API token for the target group}
        {--api-base= : Override API base URL}';

    protected $description = 'Import event and item data into Restarters';

    public function handle(DataImportService $service): int
    {
        $groupId = (int) $this->option('group-id');
        $eventId = $this->option('event-id');
        $apiToken = (string) $this->option('api-token');
        $apiBase = $this->option('api-base') ?: null;

        $itemsCsv = $this->argument('itemsCsv');
        $eventsCsv = $this->argument('eventsCsv');

        if (!$groupId) {
            $this->error('--group-id is required');
            return self::FAILURE;
        }

        if (!$apiToken) {
            $this->error('--api-token is required');
            return self::FAILURE;
        }

        try {
            if ($eventId) {
                $report = $service->importItemsForExistingEvent(
                    itemsCsv: $itemsCsv,
                    groupId: $groupId,
                    eventId: (int) $eventId,
                    apiToken: $apiToken,
                    baseUrl: $apiBase,
                );
            } else {
                if (!$eventsCsv) {
                    $this->error('eventsCsv is required in full import mode');
                    return self::FAILURE;
                }

                $report = $service->importAllEvents(
                    eventsCsv: $eventsCsv,
                    itemsCsv: $itemsCsv,
                    groupId: $groupId,
                    apiToken: $apiToken,
                    baseUrl: $apiBase,
                );
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
        $this->line(' Items failed: '.$report['items_total']['failed']);
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
                ->map(fn ($value, $key) => "{$key}={$value}")
                ->implode(', ');

            $this->line(" - [{$err['type']}] {$err['message']}".($ctx ? " ({$ctx})" : ''));
        }
    }
}

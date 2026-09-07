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
        {--api-base= : Override API base URL}
        {--dry-run : Show what would be imported without making any changes}
        {--force : Proceed even if required columns are missing}
        {--validate-only : Only validate CSV columns; make no changes}';

    protected $description = 'Import event and item data into Restarters';

    public function handle(DataImportService $service): int
    {
        $groupId = (int) $this->option('group-id');
        $eventId = $this->option('event-id');
        $apiToken = (string) $this->option('api-token');
        $apiBase = $this->option('api-base') ?: null;
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $validateOnly = (bool) $this->option('validate-only');

        $itemsCsv = $this->argument('itemsCsv');
        $eventsCsv = $this->argument('eventsCsv');

        if (!$validateOnly) {
            if (!$groupId) {
                $this->error('--group-id is required');
                return self::FAILURE;
            }

            if (!$apiToken) {
                $this->error('--api-token is required');
                return self::FAILURE;
            }
        }

        try {
            if ($validateOnly) {
                return $this->runValidation($service, $itemsCsv, $eventsCsv, (bool) $eventId);
            }

            if ($eventId) {
                $report = $service->importItemsForExistingEvent(
                    itemsCsv: $itemsCsv,
                    groupId: $groupId,
                    eventId: (int) $eventId,
                    apiToken: $apiToken,
                    baseUrl: $apiBase,
                    dryRun: $dryRun,
                    force: $force,
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
                    dryRun: $dryRun,
                    force: $force,
                );
            }

            $this->renderReport($report);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    protected function runValidation(DataImportService $service, string $itemsCsv, ?string $eventsCsv, bool $itemOnly): int
    {
        $validation = $service->validateColumns(
            itemsCsv: $itemsCsv,
            eventsCsv: $itemOnly ? null : $eventsCsv,
            itemOnly: $itemOnly,
        );

        $this->renderValidation($validation);

        return $validation['fatal'] ? self::FAILURE : self::SUCCESS;
    }

    protected function renderReport(array $report): void
    {
        $this->newLine();
        $this->line('=== Import report ===');

        if ((bool) $this->option('dry-run')) {
            $this->line('*** DRY-RUN — no changes were made ***');
        }

        if (isset($report['validation'])) {
            $v = $report['validation'];
            $this->line('Column validation:');
            $this->line(' Items missing: '.($v['items']['missing'] ? implode(', ', $v['items']['missing']) : 'none'));
            $this->line(' Items unknown: '.($v['items']['unknown'] ? implode(', ', $v['items']['unknown']) : 'none'));

            if ($v['events'] !== null) {
                $this->line(' Events missing: '.($v['events']['missing'] ? implode(', ', $v['events']['missing']) : 'none'));
                $this->line(' Events unknown: '.($v['events']['unknown'] ? implode(', ', $v['events']['unknown']) : 'none'));
            }

            if ($v['fatal']) {
                $this->line(' Validation result: FATAL (required columns missing, use --force to proceed)');
            } else {
                $this->line(' Validation result: OK');
            }

            $this->newLine();
        }
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

    protected function renderValidation(array $validation): void
    {
        $this->newLine();
        $this->line('=== Column validation ===');
        $this->newLine();

        $this->line('Items CSV:');
        $this->line(' Missing: '.($validation['items']['missing'] ? implode(', ', $validation['items']['missing']) : 'none'));
        $this->line(' Unknown: '.($validation['items']['unknown'] ? implode(', ', $validation['items']['unknown']) : 'none'));
        $this->newLine();

        if ($validation['events'] !== null) {
            $this->line('Events CSV:');
            $this->line(' Missing: '.($validation['events']['missing'] ? implode(', ', $validation['events']['missing']) : 'none'));
            $this->line(' Unknown: '.($validation['events']['unknown'] ? implode(', ', $validation['events']['unknown']) : 'none'));
            $this->newLine();
        }

        if ($validation['fatal']) {
            $this->line('Result: FATAL — required columns are missing (use --force to import anyway)');
        } else {
            $this->line('Result: OK — all required columns present');
        }
    }
}

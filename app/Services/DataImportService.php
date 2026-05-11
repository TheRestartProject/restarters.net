<?php

namespace App\Services;

use Carbon\Carbon;

class DataImportService
{
    protected array $categoryIds = [
        "Games console" => 6,
        "Watch/clock" => 7,
        "Sewing machine" => 8,
        "Iron" => 9,
        "Coffee maker" => 10,
        "Desktop computer" => 11,
        'Flat screen 15-17"' => 12,
        'Flat screen 19-20"' => 13,
        'Flat screen 22-24"' => 14,
        "Laptop large" => 15,
        "Laptop medium" => 16,
        "Laptop small" => 17,
        "Paper shredder" => 18,
        "PC accessory" => 19,
        "Printer/scanner" => 20,
        "Digital compact camera" => 21,
        "DSLR/video camera" => 22,
        "Handheld entertainment device" => 23,
        "Headphones" => 24,
        "Mobile" => 25,
        "Tablet" => 26,
        'Flat screen 26-30"' => 27,
        'Flat screen 32-37"' => 28,
        "Hi-Fi integrated" => 29,
        "Hi-Fi separates" => 30,
        "Musical instrument" => 31,
        "Portable radio" => 32,
        "Projector" => 33,
        "TV and gaming-related accessories" => 34,
        "Aircon/dehumidifier" => 35,
        "Decorative or safety lights" => 36,
        "Fan" => 37,
        "Hair & beauty item" => 38,
        "Kettle" => 39,
        "Lamp" => 40,
        "Power tool" => 41,
        "Small kitchen item" => 42,
        "Toaster" => 43,
        "Toy" => 44,
        "Vacuum" => 45,
        "Misc (powered)" => 46,
        "Furniture" => 47,
        "Bicycle" => 48,
        "Clothing/textile" => 49,
        "Misc (unpowered)" => 50,
        "Hand tool" => 51,
        "Jewellery" => 52,
    ];

    public function __construct(
        protected CsvReader $csvReader,
    ) {
    }

    public function importAllEvents(
        string $eventsCsv,
        string $itemsCsv,
        int $groupId,
        string $apiToken,
        ?string $baseUrl = null,
    ): array {
        $apiClient = new RestartersApiClient($apiToken, $baseUrl);

        $report = $this->newReport();
        $reportEventIds = [];

        $allEvents = $this->csvReader->read($eventsCsv);
        $allItems = $this->csvReader->read($itemsCsv);

        if (empty($allEvents)) {
            $this->recordError($report, 'no_events', 'No events found in events CSV', ['operation' => 'main']);
            $report['event_ids'] = $reportEventIds;
            return $report;
        }

        $itemsByFk = [];
        foreach ($allItems as $item) {
            $fk = $item['event'] ?? '';
            $itemsByFk[$fk] ??= [];
            $itemsByFk[$fk][] = $item;
        }

        foreach ($allEvents as $event) {
            $eventDate = trim($event['event date'] ?? '');
            $venueName = trim($event['venue name'] ?? '');

            if (!$eventDate || !$venueName) {
                $this->recordError($report, 'event_missing_key_fields', 'Skipping event with missing date or venue name', [
                    'event_date' => $eventDate,
                    'venue_name' => $venueName,
                    'operation' => 'process_event',
                ]);
                continue;
            }

            $eventKey = "{$eventDate} - {$venueName}";

            [$eventId, $eventStatus] = $this->createOrFindEvent($apiClient, $event, $groupId, $report);
            $report['events'][$eventStatus]++;

            if (!$eventId) {
                $this->recordError($report, 'event_create_failed_no_id', "Event creation failed for {$eventKey}; skipping its items", [
                    'event_key' => $eventKey,
                    'operation' => 'process_event',
                ]);
                continue;
            }

            $reportEventIds[$eventKey] = $eventId;
            $report['items_per_event'][$eventKey] ??= $this->emptyItemStats();

            $itemsForEvent = [];
            foreach ($itemsByFk as $fk => $items) {
                if (str_starts_with($fk, $eventKey)) {
                    $itemsForEvent = array_merge($itemsForEvent, $items);
                }
            }

            if (empty($itemsForEvent)) {
                $this->recordError($report, 'no_items_for_event', "No items found for event {$eventKey}", [
                    'event_key' => $eventKey,
                    'operation' => 'process_event',
                ]);
                continue;
            }

            foreach ($itemsForEvent as $item) {
                $status = $this->createItemForEvent($apiClient, $item, $eventId, $report);
                $report['items_per_event'][$eventKey][$status]++;
                $report['items_total'][$status]++;
            }
        }

        $report['event_ids'] = $reportEventIds;

        return $report;
    }

    public function importItemsForExistingEvent(
        string $itemsCsv,
        int $groupId,
        int $eventId,
        string $apiToken,
        ?string $baseUrl = null,
    ): array {
        $apiClient = new RestartersApiClient($apiToken, $baseUrl);

        $report = $this->newReport();
        $reportKey = "event_id:{$eventId}";
        $report['event_ids'][$reportKey] = $eventId;
        $report['items_per_event'][$reportKey] = $this->emptyItemStats();

        $this->assertEventExistsInGroup($apiClient, $groupId, $eventId);

        $items = $this->csvReader->read($itemsCsv);

        foreach ($items as $item) {
            $status = $this->createItemForEvent($apiClient, $item, $eventId, $report);
            $report['items_per_event'][$reportKey][$status]++;
            $report['items_total'][$status]++;
        }

        return $report;
    }

    protected function assertEventExistsInGroup(RestartersApiClient $apiClient, int $groupId, int $eventId): void
    {
        $response = $apiClient->get("/groups/{$groupId}/events");
        $events = $response['data'] ?? [];

        foreach ($events as $event) {
            if ((int) ($event['id'] ?? 0) === $eventId) {
                return;
            }
        }

        throw new \RuntimeException("Event {$eventId} was not found in group {$groupId}");
    }

    protected function createOrFindEvent(RestartersApiClient $apiClient, array $event, int $groupId, array &$report): array
    {
        $venue = $event['venue name'] ?? '';
        $date = $event['event date'] ?? '';
        $ctx = ['venue' => $venue, 'date' => $date, 'operation' => 'create_event'];

        $existingId = $this->findExistingEventId($apiClient, $venue, $date, $groupId, $report);
        if ($existingId) {
            return [$existingId, 'existing'];
        }

        $payload = $this->transformEvent($event, $groupId);
        $resp = $apiClient->post('/events', $payload);

        if ($resp && isset($resp['id'])) {
            return [(int) $resp['id'], 'created'];
        }

        $this->recordError($report, 'event_create_failed', "Failed to create event ".($payload['title'] ?? ''), $ctx);

        return [null, 'failed'];
    }

    protected function findExistingEventId(
        RestartersApiClient $apiClient,
        string $venue,
        string $date,
        int $groupId,
        array &$report
    ): ?int {
        $ctx = ['venue' => $venue, 'date' => $date, 'operation' => 'event_exists'];

        $resp = $apiClient->get("/groups/{$groupId}/events");

        if (!$resp || !isset($resp['data'])) {
            $this->recordError($report, 'event_exists_no_data', "No data when checking existence for event '{$venue}' on '{$date}'", $ctx);
            return null;
        }

        foreach ($resp['data'] as $event) {
            $eventTitle = trim($event['title'] ?? '');
            $startValue = $event['start'] ?? null;

            try {
                $eventDate = Carbon::parse($startValue)->toDateString();
            } catch (\Throwable $e) {
                $this->recordError($report, 'event_date_parse_error', "Failed to parse event start datetime '{$startValue}' for existing event", [
                    ...$ctx,
                    'raw_start' => $startValue,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if ($eventTitle === $venue && $eventDate === $date) {
                return (int) $event['id'];
            }
        }

        return null;
    }

    protected function createItemForEvent(RestartersApiClient $apiClient, array $item, int $eventId, array &$report): string
    {
        $itemDesc = $item['what is it?'] ?? '';
        $ctx = [
            'event_id' => $eventId,
            'item_description' => $itemDesc,
            'operation' => 'create_item',
        ];

        $payload = $this->transformItem($item, $eventId);

        if (!$payload) {
            $this->recordError($report, 'item_skipped_unknown_category', "Skipping item '{$itemDesc}' due to unknown or missing category", $ctx);
            return 'skipped_unknown_category';
        }

        $resp = $apiClient->post('/devices', $payload);

        if ($resp && isset($resp['id'])) {
            return 'created';
        }

        $this->recordError($report, 'item_create_failed', "Failed to create item ".($payload['item_type'] ?? '')." for event {$eventId}", $ctx);
        return 'failed';
    }

    protected function transformEvent(array $event, int $groupId): array
    {
        $date = trim($event['event date'] ?? '');
        $startTime = trim($event['start time'] ?? '');
        $endTime = trim($event['end time'] ?? '');
        $timezone = trim($event['timezone'] ?? '') ?: 'Europe/Madrid';
        $link = trim($event['event link'] ?? '') ?: trim($event['event link (optional)'] ?? '');

        $payload = [
            'groupid' => $groupId,
            'start' => $this->getDateTimeUtc($date, $startTime, $timezone),
            'end' => $this->getDateTimeUtc($date, $endTime, $timezone),
            'title' => trim($event['venue name'] ?? ''),
            'description' => trim($event['event description'] ?? '...'),
            'location' => trim($event['venue address'] ?? ''),
            'link' => $link,
            'timezone' => $timezone,
        ];

        $online = strtolower(trim($event['online'] ?? ''));
        if ($online === 'true') {
            $payload['online'] = true;
        } elseif ($online === 'false') {
            $payload['online'] = false;
        }

        return array_filter($payload, fn ($value) => $value !== '' && $value !== null);
    }

    protected function transformItem(array $item, int $eventId): ?array
    {
        $poweredCategory = $item['powered_category'] ?? '';
        $unpoweredCategory = $item['unpowered_category'] ?? '';

        if ($poweredCategory === 'Misc') {
            $categoryName = 'Misc (powered)';
        } elseif ($unpoweredCategory === 'Misc') {
            $categoryName = 'Misc (unpowered)';
        } else {
            $categoryName = $poweredCategory ?: $unpoweredCategory;
        }

        $categoryId = $this->categoryIds[$categoryName] ?? null;

        if (!$categoryId) {
            return null;
        }

        $repairStatusRaw = trim($item['repair status'] ?? '');
        $repairStatus = $repairStatusRaw === 'Unknown' ? null : $repairStatusRaw;

        $payload = [
            'eventid' => $eventId,
            'category' => $categoryId,
            'item_type' => $item['what is it?'] ?? '',
            'brand' => $item['brand'] ?? '',
            'model' => $item['model'] ?? '',
            'age' => $item['age'] ?? '',
            'estimate' => $item['weight estimate'] ?? '',
            'problem' => $this->getAssessment($item),
            'notes' => $item['notes'] ?? '',
            'repair_status' => $repairStatus,
            'next_steps' => $item['next steps (if status = repairable)'] ?? '',
            'spare_parts' => $this->mapSpareParts($item['spare parts required?'] ?? ''),
            'barrier' => $this->mapBarrier($item['main barrier to repair'] ?? ''),
        ];

        return array_filter($payload, fn ($value) => $value !== '' && $value !== null);
    }

    protected function mapSpareParts(string $value): ?string
    {
        $normalized = strtolower(trim($value));

        return match (true) {
            in_array($normalized, ['no', 'not needed', 'not required']) => 'No',
            in_array($normalized, ['manufacturer', 'from manufacturer']) => 'Manufacturer',
            in_array($normalized, ['third party', 'from 3rd party', '3rd party', 'from third party']) => 'Third party',
            default => null,
        };
    }

    protected function mapBarrier(string $value): ?string
    {
        $normalized = strtolower(trim($value));

        return match (true) {
            $normalized === 'spare parts not available' => 'Spare parts not available',
            $normalized === 'spare parts too expensive' => 'Spare parts too expensive',
            $normalized === 'no way to open the product' => 'No way to open the product',
            $normalized === 'repair information not available' => 'Repair information not available',
            $normalized === 'lack of equipment' => 'Lack of equipment',
            default => null,
        };
    }

    protected function getAssessment(array $item): string
    {
        $assessment = trim($item['Assessment'] ?? '');

        if ($assessment) {
            return rtrim($assessment, '. ') . '.';
        }

        return '';
    }

    protected function getDateTimeUtc(?string $date, ?string $time, ?string $timezone): ?string
    {
        if (!$date || !$time || !$timezone) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$time}", $timezone)
                ->utc()
                ->format('Y-m-d\TH:i:s\Z');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function newReport(): array
    {
        return [
            'events' => ['created' => 0, 'existing' => 0, 'failed' => 0],
            'items_per_event' => [],
            'items_total' => ['created' => 0, 'skipped_unknown_category' => 0, 'failed' => 0],
            'errors' => [],
            'event_ids' => [],
        ];
    }

    protected function emptyItemStats(): array
    {
        return [
            'created' => 0,
            'skipped_unknown_category' => 0,
            'failed' => 0,
        ];
    }

    protected function recordError(array &$report, string $type, string $message, array $context = []): void
    {
        $report['errors'][] = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
        ];
    }
}

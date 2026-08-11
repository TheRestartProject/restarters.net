<?php

namespace App\Services\Ords;

use App\Device;
use App\Helpers\Iso3166;
use Carbon\Carbon;

/**
 * Maps a Fixometer device row onto an Open Repair Data Standard v0.3 record.
 *
 * Expects the device to carry the aliased columns selected by
 * RepairController::buildBaseRepairQuery and an eager-loaded `barriers`
 * relation.
 *
 * @OA\Schema(
 *     title="OrdsRepair",
 *     schema="OrdsRepair",
 *     description="A repair record in Open Repair Data Standard v0.3. Columns and their order follow the standard; every missing value is null in JSON and an empty string in CSV.",
 *     @OA\Property(
 *          property="id",
 *          description="Stable identifier the consumer upserts on, namespaced by the instance's assigned prefix.",
 *          type="string",
 *          example="restarters_12345"
 *     ),
 *     @OA\Property(property="data_provider", description="Organisation publishing the record.", type="string", example="The Restart Project"),
 *     @OA\Property(property="country", description="ISO 3166-1 alpha-3 country of the event's group.", type="string", nullable=true, example="GBR"),
 *     @OA\Property(property="partner_product_category", description="Our own category, plus the free-text item type where present.", type="string", nullable=true, example="Desktop computer ~ Tower PC"),
 *     @OA\Property(property="product_category", description="The standard's category name.", type="string", nullable=true, example="Desktop computer"),
 *     @OA\Property(property="product_category_id", description="The standard's category id. Null for unpowered items, which the standard publishes without one.", type="integer", nullable=true, example=4),
 *     @OA\Property(property="brand", type="string", nullable=true, example="Dell"),
 *     @OA\Property(property="year_of_manufacture", description="Derived from the event year minus the item's age; not stored directly.", type="string", nullable=true, example="2015"),
 *     @OA\Property(property="product_age", description="Age in years at the time of the event. Null when not recorded.", type="number", nullable=true, example=7),
 *     @OA\Property(property="repair_status", type="string", enum={"Fixed", "Repairable", "End of life", "Unknown"}, example="Fixed"),
 *     @OA\Property(property="repair_barrier_if_end_of_life", description="Only set when repair_status is End of life.", type="string", nullable=true, example="Spare parts unavailable"),
 *     @OA\Property(property="group_identifier", description="Name of the group that ran the event.", type="string", nullable=true, example="Restarters HQ"),
 *     @OA\Property(property="event_date", type="string", format="date", nullable=true, example="2022-09-18"),
 *     @OA\Property(
 *          property="problem",
 *          description="Volunteer-written description of the fault, with contact details and identifiers redacted. Personal names are not pattern-detectable and are not removed.",
 *          type="string",
 *          nullable=true,
 *          example="Screen flickers when the lid is moved"
 *     )
 * )
 */
class OrdsRecordMapper
{
    public const UNASSIGNED_ID_PREFIX = 'UNASSIGNED_';

    public const STANDARD = 'Open Repair Data Standard v0.3';

    public const COLUMNS = [
        'id',
        'data_provider',
        'country',
        'partner_product_category',
        'product_category',
        'product_category_id',
        'brand',
        'year_of_manufacture',
        'product_age',
        'repair_status',
        'repair_barrier_if_end_of_life',
        'group_identifier',
        'event_date',
        'problem',
    ];

    private readonly string $idPrefix;

    private readonly mixed $dataProvider;

    /** @var array<string,array{0:string,1:int}> */
    private readonly array $poweredCategories;

    /** @var array<string,string> */
    private readonly array $unpoweredCategories;

    private readonly mixed $unpoweredFallback;

    /** @var array<int,string> */
    private readonly array $repairStatuses;

    private readonly mixed $repairStatusUnknown;

    /** @var array<string,string> */
    private readonly array $barrierVocabulary;

    /**
     * Config is resolved once here rather than per row. map() runs up to 1000
     * times per request and each config() call copies a whole vocabulary array
     * out by value to read one key.
     */
    public function __construct(private readonly ProblemTextScrubber $scrubber)
    {
        $this->idPrefix = trim((string) config('ords.id_prefix'));
        $this->dataProvider = config('ords.data_provider');
        $this->poweredCategories = config('ords.categories_powered');
        $this->unpoweredCategories = config('ords.categories_unpowered');
        $this->unpoweredFallback = config('ords.categories_unpowered_fallback');
        $this->repairStatuses = config('ords.repair_status');
        $this->repairStatusUnknown = config('ords.repair_status_unknown');
        $this->barrierVocabulary = config('ords.barriers');
    }

    public function resetRedactions(): void
    {
        $this->scrubber->reset();
    }

    /** @return array<string,int> */
    public function redactionCounts(): array
    {
        return $this->scrubber->counts();
    }

    /** @return array<string,mixed> keyed in ORDS column order */
    public function map(Device $device): array
    {
        $eventDate = $this->eventDate($device);
        $productAge = $this->productAge($device);
        [$productCategory, $productCategoryId] = $this->productCategory($device);

        return [
            // Trimmed to match the controller's guard: untrimmed, " ifixit_" would
            // pass validation and emit ids with a leading space.
            'id' => $this->idPrefix.$device->iddevices,
            'data_provider' => $this->dataProvider,
            'country' => Iso3166::alpha3($device->ords_country_code),
            'partner_product_category' => $this->partnerProductCategory($device),
            'product_category' => $productCategory,
            'product_category_id' => $productCategoryId,
            'brand' => $this->scrubbed($device->brand),
            'year_of_manufacture' => $this->yearOfManufacture($eventDate, $productAge),
            'product_age' => $productAge,
            'repair_status' => $this->repairStatus($device),
            'repair_barrier_if_end_of_life' => $this->repairBarrier($device),
            'group_identifier' => $this->nullIfBlank($device->ords_group_name),
            'event_date' => $eventDate?->toDateString(),
            'problem' => $this->problem($device),
        ];
    }

    /**
     * "<category> ~ <item_type>" when an item type is present, bare category
     * otherwise -- follows The Restart Project's own published row convention.
     */
    private function partnerProductCategory(Device $device): ?string
    {
        // Category name comes from our own `categories` table and is not
        // free text; item_type is typed by a volunteer at the event.
        $category = $this->nullIfBlank($device->ords_category_name);
        $itemType = $this->scrubbed($device->item_type);

        if ($category === null) {
            return $itemType;
        }

        return $itemType === null ? $category : "{$category} ~ {$itemType}";
    }

    /** @return array{0: ?string, 1: ?int} [product_category, product_category_id] */
    private function productCategory(Device $device): array
    {
        $name = $this->nullIfBlank($device->ords_category_name);

        if (! $device->ords_category_powered) {
            // ORA's unpowered dataset carries no product_category_id.
            return [
                $this->unpoweredCategories[$name] ?? $this->unpoweredFallback,
                null,
            ];
        }

        // Unmapped powered category is a vocabulary gap, not a data error: fall
        // back to our own name with a null id rather than dropping the record.
        return $this->poweredCategories[$name] ?? [$name, null];
    }

    /**
     * `devices.age` is DECIMAL(5,2) UNSIGNED ZEROFILL NOT NULL DEFAULT 0, so
     * MySQL returns it zero-padded ("005.00") and 0 means "not recorded", not
     * a real age. is_numeric also covers instances still on the old free-text
     * VARCHAR column.
     */
    private function productAge(Device $device): int|float|null
    {
        $age = $device->age;

        if ($age === null || ! is_numeric($age)) {
            return null;
        }

        $age = (float) $age;

        if ($age <= 0) {
            return null;
        }

        return $age == (int) $age ? (int) $age : $age;
    }

    /**
     * Not stored, so derived: the year the event ran minus the item's age.
     * ORDS wants a 4-digit string; anything we cannot derive is omitted.
     */
    private function yearOfManufacture(?Carbon $eventDate, int|float|null $productAge): ?string
    {
        if ($eventDate === null || $productAge === null) {
            return null;
        }

        $year = (int) round($eventDate->year - $productAge);

        // ORDS constrains this to ^[0-9]{4}$; clamp rather than emit a rejected value.
        if ($year < 1000 || $year > 9999) {
            return null;
        }

        return (string) $year;
    }

    private function eventDate(Device $device): ?Carbon
    {
        $startUtc = $device->ords_event_start_utc;

        if (empty($startUtc)) {
            return null;
        }

        $date = Carbon::parse($startUtc, 'UTC');
        $timezone = $this->nullIfBlank($device->ords_event_timezone);

        if ($timezone !== null) {
            try {
                $date = $date->setTimezone($timezone);
            } catch (\Throwable) {
                // Unrecognised timezone: keep UTC rather than drop a required column.
            }
        }

        return $date;
    }

    private function repairStatus(Device $device): string
    {
        return $this->repairStatuses[$device->repair_status]
            ?? $this->repairStatusUnknown;
    }

    /** Devices can carry several barriers; ORDS has one column, so the first wins. */
    private function repairBarrier(Device $device): ?string
    {
        if ((int) $device->repair_status !== Device::REPAIR_STATUS_ENDOFLIFE) {
            return null;
        }

        $barrier = $device->barriers->first();

        if ($barrier === null) {
            return null;
        }

        return $this->barrierVocabulary[$barrier->barrier] ?? null;
    }

    /**
     * Always emitted and always scrubbed. The scrubber removes contact details
     * and identifiers but cannot remove personal names, which are not
     * pattern-detectable; see ProblemTextScrubber.
     */
    private function problem(Device $device): ?string
    {
        return $this->scrubbed($device->problem);
    }

    /**
     * Every volunteer-written column goes through here. `problem` is the
     * obvious one, but `item_type` and `brand` are typed by hand at an event
     * with no vocabulary behind them, and owner-identifying text turns up in
     * both ("Jane's kettle"). All three are published, so all three redact.
     */
    private function scrubbed(?string $value): ?string
    {
        return $this->nullIfBlank($this->scrubber->scrub($value));
    }

    private function nullIfBlank(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}

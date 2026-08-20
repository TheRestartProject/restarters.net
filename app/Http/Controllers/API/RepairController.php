<?php

namespace App\Http\Controllers\API;

use App\Device;
use App\Http\Controllers\Controller;
use App\Services\Ords\OrdsRecordMapper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\EscapeFormula;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Open Repair Data Standard v0.3 export for the Open Repair Alliance.
 *
 * Only approved events on approved groups are visible, soft-deletes excluded.
 *
 * @see config/ords.php for the vocabulary maps and the id namespace guard.
 */
class RepairController extends Controller
{
    /**
     * What "changed" means to an incremental consumer.
     *
     * Keying off `devices.updated_at` alone hides work that only ever touched
     * a parent row: approving an old event, or approving the group it belongs
     * to, makes devices exportable that every earlier pull filtered out, while
     * their own updated_at stands still. Those devices would never appear in
     * an incremental crawl again.
     *
     * All three columns are NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE
     * CURRENT_TIMESTAMP (see 2018_05_10_085751_rename_modified_at), so GREATEST
     * needs no null handling and the values maintain themselves.
     *
     * The filter and the watermark both use this, so a consumer that resumes
     * from max_updated_at resumes from the same clock it was filtered on.
     */
    private const CHANGED_AT = 'GREATEST(devices.updated_at, events.updated_at, groups.updated_at)';

    public function __construct(private readonly OrdsRecordMapper $mapper)
    {
    }

    /**
     * @OA\Get(
     *      path="/api/v2/repairs",
     *      operationId="listRepairsv2",
     *      tags={"Repairs"},
     *      summary="Export repair records in Open Repair Data Standard v0.3",
     *      description="Bulk export of repair records for the Open Repair Alliance. Covers approved events on approved groups only. Returns JSON by default, or CSV when format=csv. Administrator role required.",
     *      @OA\Parameter(
     *          name="api_token",
     *          description="A valid user API token. The caller must hold the Administrator role.",
     *          required=true,
     *          in="query",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="format",
     *          description="Response format",
     *          required=false,
     *          in="query",
     *          @OA\Schema(type="string", enum={"json", "csv"}, default="json")
     *      ),
     *      @OA\Parameter(
     *          name="updated_since",
     *          description="Only records whose device, event or group row changed at or after this time",
     *          required=false,
     *          in="query",
     *          @OA\Schema(type="string", format="date-time")
     *      ),
     *      @OA\Parameter(
     *          name="event_start",
     *          description="Only records from events starting at or after this date",
     *          required=false,
     *          in="query",
     *          @OA\Schema(type="string", format="date")
     *      ),
     *      @OA\Parameter(
     *          name="event_end",
     *          description="Only records from events starting at or before this date",
     *          required=false,
     *          in="query",
     *          @OA\Schema(type="string", format="date")
     *      ),
     *      @OA\Parameter(
     *          name="powered",
     *          description="Restrict to powered or unpowered categories. Unfiltered when absent.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(type="boolean")
     *      ),
     *      @OA\Parameter(
     *          name="page",
     *          description="Page number, 1-based",
     *          required=false,
     *          in="query",
     *          @OA\Schema(type="integer", minimum=1, default=1)
     *      ),
     *      @OA\Parameter(
     *          name="per_page",
     *          description="Records per page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(type="integer", minimum=1, maximum=1000, default=100)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="data",
     *                      type="array",
     *                      @OA\Items(ref="#/components/schemas/OrdsRepair")
     *                  ),
     *                  @OA\Property(
     *                      property="meta",
     *                      type="object",
     *                      @OA\Property(property="page", type="integer"),
     *                      @OA\Property(property="per_page", type="integer"),
     *                      @OA\Property(property="total", type="integer"),
     *                      @OA\Property(property="last_page", type="integer"),
     *                      @OA\Property(property="standard", type="string"),
     *                      @OA\Property(property="columns", type="array", @OA\Items(type="string"))
     *                  ),
     *                  @OA\Property(
     *                      property="sync",
     *                      type="object",
     *                      @OA\Property(property="generated_at", type="string", format="date-time"),
     *                      @OA\Property(property="max_updated_at", type="string", format="date-time", nullable=true)
     *                  )
     *              )
     *          ),
     *          @OA\MediaType(
     *              mediaType="text/csv",
     *              @OA\Schema(type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden - the authenticated user is not an Administrator",
     *      ),
     *      @OA\Response(
     *          response=503,
     *          description="Export is not configured: no id namespace or data provider name has been set",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string")
     *          )
     *      ),
     *     )
     */
    public function listRepairsv2(Request $request): Response
    {
        // Checked before the config guard so the export's configuration state
        // is not readable by an account that cannot use the export anyway.
        if (! $request->user()->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        if ($guard = $this->guardExportConfig()) {
            return $guard;
        }

        $this->normalisePoweredInput($request);

        $validated = $request->validate([
            'format' => ['nullable', 'string', 'in:json,csv'],
            'updated_since' => ['nullable', 'date'],
            'event_start' => ['nullable', 'date'],
            'event_end' => ['nullable', 'date'],
            'powered' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('ords.pagination.max_per_page')],
        ]);

        $query = $this->buildBaseRepairQuery();
        $this->applyFilters($query, $validated);

        // Formatted once here rather than in each responder: CSV and JSON carry
        // the same watermark, they just carry it in different places.
        $rawMaxUpdatedAt = (clone $query)->max(DB::raw(self::CHANGED_AT));
        $maxUpdatedAt = $rawMaxUpdatedAt ? Carbon::parse($rawMaxUpdatedAt)->toIso8601String() : null;

        $perPage = (int) ($validated['per_page'] ?? config('ords.pagination.default_per_page'));
        $paginator = $query->paginate($perPage);

        $this->mapper->resetRedactions();
        $records = $paginator->getCollection()
            ->map(fn (Device $device) => $this->mapper->map($device))
            ->values();

        $this->logExport($request, $validated, $paginator);

        if (($validated['format'] ?? 'json') === 'csv') {
            return $this->csvResponse($records->all(), $paginator, $maxUpdatedAt);
        }

        return $this->jsonResponse($records->all(), $paginator, $maxUpdatedAt);
    }

    /** Laravel's `boolean` rule rejects "true"/"false"; normalise those before validation. */
    private function normalisePoweredInput(Request $request): void
    {
        if (! $request->has('powered')) {
            return;
        }

        $raw = $request->input('powered');

        // filter_var maps "" and null to false rather than firing
        // FILTER_NULL_ON_FAILURE, so an empty `?powered=` would narrow the
        // export to unpowered items. Null instead, which applyFilters skips.
        if ($raw === null || $raw === '') {
            $request->merge(['powered' => null]);

            return;
        }

        $normalised = filter_var($raw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($normalised !== null) {
            $request->merge(['powered' => $normalised]);
        }
    }

    /**
     * `id_prefix` and `data_provider` are instance-specific with no safe
     * default. Both are checked for emptiness, not just presence, because an
     * env var set to "" yields an empty string rather than falling back to
     * any default. Normalisation lives on the mapper so the value checked here
     * is exactly the value emitted on every row.
     */
    private function guardExportConfig(): ?JsonResponse
    {
        $prefix = OrdsRecordMapper::configuredIdPrefix();

        if ($prefix === '' || $prefix === OrdsRecordMapper::UNASSIGNED_ID_PREFIX) {
            return response()->json([
                'message' => 'ORDS export is not configured: no partner id namespace has been assigned.',
            ], 503);
        }

        if (OrdsRecordMapper::configuredDataProvider() === '') {
            return response()->json([
                'message' => 'ORDS export is not configured: no data provider name has been set.',
            ], 503);
        }

        return null;
    }

    private function jsonResponse(array $records, LengthAwarePaginator $paginator, ?string $maxUpdatedAt): JsonResponse
    {
        return response()->json([
            'data' => $records,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'standard' => OrdsRecordMapper::STANDARD,
                'columns' => OrdsRecordMapper::COLUMNS,
            ],
            'sync' => [
                'generated_at' => Carbon::now()->toIso8601String(),
                'max_updated_at' => $maxUpdatedAt,
            ],
        ]);
    }

    /** No envelope for pagination/sync metadata in CSV, so it travels in headers instead. */
    private function csvResponse(array $records, LengthAwarePaginator $paginator, ?string $maxUpdatedAt): StreamedResponse
    {
        return response()->streamDownload(function () use ($records) {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            // `problem` is free text; a cell opening with = + - @ executes as a formula on open.
            $csv->addFormatter([new EscapeFormula(), 'escapeRecord']);
            $csv->insertOne(OrdsRecordMapper::COLUMNS);

            foreach ($records as $record) {
                // ORDS uses an empty string for every missing value.
                $csv->insertOne(array_map(
                    fn ($value) => $value === null ? '' : $value,
                    $record
                ));
            }
        }, 'ords-repairs.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Total-Count' => (string) $paginator->total(),
            'X-Page' => (string) $paginator->currentPage(),
            'X-Per-Page' => (string) $paginator->perPage(),
            'X-Last-Page' => (string) $paginator->lastPage(),
            'X-Max-Updated-At' => $maxUpdatedAt ?? '',
        ]);
    }

    private function buildBaseRepairQuery(): Builder
    {
        return Device::query()
            ->join('events', 'events.idevents', '=', 'devices.event')
            ->join('groups', 'groups.idgroups', '=', 'events.group')
            ->join('categories', 'categories.idcategories', '=', 'devices.category')
            // Only events are soft-deletable; a removed group takes its rows
            // out of the inner join.
            ->whereNull('events.deleted_at')
            ->where('events.approved', true)
            ->where('groups.approved', true)
            // Ordered: the mapper emits only the first barrier, and an unordered
            // relation would let a device publish a different one between exports.
            ->with(['barriers' => fn ($q) => $q->orderBy('barriers.id')])
            // Pinned, not devices.*: the exported surface should be a property
            // of this query rather than an accident of what the mapper happens
            // to read. It also keeps the mediumText `notes` column, which the
            // export never uses, out of a 1000-row hydration.
            ->select(
                'devices.iddevices',
                'devices.brand',
                'devices.item_type',
                'devices.age',
                'devices.repair_status',
                'devices.problem',
                'devices.updated_at',
                'categories.name as ords_category_name',
                'categories.powered as ords_category_powered',
                'groups.name as ords_group_name',
                'groups.country_code as ords_country_code',
                'events.event_start_utc as ords_event_start_utc',
                'events.timezone as ords_event_timezone',
            )
            // Stable ordering so pagination can't skip or repeat rows between pages.
            ->orderBy('devices.iddevices', 'asc');
    }

    private function applyFilters(Builder $query, array $validated): void
    {
        if (! empty($validated['updated_since'])) {
            // 'UTC' passed explicitly, as OrdsRecordMapper::eventDate does: an
            // offset in the input still wins, but a bare value no longer depends
            // on config('app.timezone') happening to be UTC.
            $updatedSince = Carbon::parse($validated['updated_since'], 'UTC')->setTimezone('UTC')->toDateTimeString();
            $query->whereRaw(self::CHANGED_AT.' >= ?', [$updatedSince]);
        }

        if (! empty($validated['event_start'])) {
            // toDateTimeString, not toIso8601String, and the same for the end
            // bound below: `events.event_start_utc` is a plain DATETIME and the
            // connection sets no session timezone, so MySQL 8.0.19+ reads a
            // trailing "+00:00" as an offset and shifts the bound into the
            // server's timezone. `updated_since` already binds this way.
            $start = Carbon::parse($validated['event_start'], 'UTC')->setTimezone('UTC')->toDateTimeString();
            $query->where('events.event_start_utc', '>=', $start);
        }

        if (! empty($validated['event_end'])) {
            $raw = trim((string) $validated['event_end']);
            $end = Carbon::parse($raw, 'UTC');

            // A date-only bound reads as "include that day"; left at 00:00 it'd
            // exclude it entirely. Decided from the raw input rather than from
            // the parsed value's local H:i:s, which reads "00:00:00" for a full
            // timestamp that happens to land on midnight in its own offset:
            // event_end=2024-06-15T00:00:00-05:00 means 05:00 UTC, and widening
            // that to end-of-day admitted a further 24 hours of events.
            if (! preg_match('~\d:\d~', $raw)) {
                $end = $end->endOfDay();
            }

            $query->where('events.event_start_utc', '<=', $end->setTimezone('UTC')->toDateTimeString());
        }

        // Unfiltered by default: ORA publishes powered/unpowered as separate datasets.
        if (array_key_exists('powered', $validated) && $validated['powered'] !== null) {
            $query->where('categories.powered', (bool) $validated['powered']);
        }
    }

    /**
     * Logged on every export, not only when something was redacted. This is a
     * bulk export of volunteer-written text reachable by any account with a
     * token, so who pulled how much and when is the only record available
     * afterwards for a subject-access request or an abuse report. The redaction
     * counters carry no text, only tallies.
     *
     * @param array<string,mixed> $validated
     */
    private function logExport(Request $request, array $validated, LengthAwarePaginator $paginator): void
    {
        Log::info('ORDS export served', [
            'user_id' => $request->user()?->id,
            'format' => $validated['format'] ?? 'json',
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'records' => $paginator->count(),
            'total' => $paginator->total(),
            'filters' => array_intersect_key($validated, array_flip([
                'updated_since', 'event_start', 'event_end', 'powered',
            ])),
            'redactions' => $this->mapper->redactionCounts(),
        ]);
    }
}

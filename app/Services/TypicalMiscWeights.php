<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The typical weight of a Misc item: the median of the weights entered for Misc items at events in the last
 * 12 months, worked out separately for powered and unpowered Misc. Misc is any category without a weight of its
 * own. It is cached for a day (an hour while there are no Misc weights yet), and held for up to an hour in a
 * long-running process once read.
 */
class TypicalMiscWeights
{
    public const MONTHS = 12;

    private const CACHE_KEY = 'typical-misc-weights';

    // Long-running processes such as queue workers re-read the cache after this many seconds.
    private const HOLD_SECONDS = 3600;

    private ?array $weights = null;

    private int $readAt = 0;

    public function get(bool $powered): float
    {
        if ($this->weights === null || time() - $this->readAt > self::HOLD_SECONDS) {
            $this->weights = Cache::get(self::CACHE_KEY);

            if ($this->weights === null) {
                $this->weights = $this->calculate();
                // Until there are Misc weights to take a median of, check again soon.
                $empty = ! array_filter($this->weights);
                Cache::put(self::CACHE_KEY, $this->weights, $empty ? Carbon::now()->addHour() : Carbon::now()->addDay());
            }

            $this->readAt = time();
        }

        return $this->weights[$powered ? 'powered' : 'unpowered'];
    }

    public function forget(): void
    {
        $this->weights = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{powered: float, unpowered: float}
     */
    public function calculate(): array
    {
        $result = [];

        foreach (['powered' => 1, 'unpowered' => 0] as $key => $powered) {
            $weights = DB::table('devices')
                ->join('categories', 'categories.idcategories', '=', 'devices.category')
                ->join('events', 'events.idevents', '=', 'devices.event')
                ->where('categories.weight', 0)
                ->where('categories.powered', $powered)
                ->where('devices.estimate', '>', 0)
                ->whereNull('events.deleted_at')
                ->where('events.event_start_utc', '>=', Carbon::now()->subMonths(self::MONTHS))
                ->where('events.event_start_utc', '<=', Carbon::now())
                ->orderBy('devices.estimate')
                ->pluck('devices.estimate')
                ->map(fn ($weight) => (float) $weight)
                ->all();

            $result[$key] = self::median($weights);
        }

        return $result;
    }

    /**
     * @param float[] $sorted ascending
     */
    public static function median(array $sorted): float
    {
        $count = count($sorted);

        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);

        return $count % 2 ? $sorted[$middle] : ($sorted[$middle - 1] + $sorted[$middle]) / 2;
    }
}

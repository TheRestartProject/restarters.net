<?php

namespace App\Jobs;

use App\Helpers\Fixometer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class RefreshLoginStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        $stats = Fixometer::computeStats();
        Cache::put('all_stats', $stats, Fixometer::STATS_TTL);
        Cache::put('all_stats_fresh', true, Fixometer::STATS_FRESH_TTL);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class YesterdayBanner
{
    public function handle(Request $request, Closure $next)
    {
        if (env('YESTERDAY_MODE') === 'true') {
            $snapshotFile = storage_path('framework/yesterday-snapshot.txt');
            $snapshotTime = file_exists($snapshotFile)
                ? trim(file_get_contents($snapshotFile))
                : 'unknown time';
            view()->share('yesterdaySnapshotTime', $snapshotTime);
        }

        return $next($request);
    }
}

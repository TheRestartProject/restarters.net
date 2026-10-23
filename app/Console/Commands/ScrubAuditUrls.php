<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off (but idempotent) cleanup for audit URLs written before
 * App\Auditing\SanitisedUrlResolver was introduced.
 *
 * Those rows stored Request::fullUrl(), so any audited write authenticated
 * with `?api_token=` left a VALID API TOKEN sitting in the audits table.
 * Fixing the resolver stops new rows carrying one; this removes the ones
 * already written, which no code change can do on its own.
 *
 * Safe to re-run: rows with no query string are left untouched.
 */
class ScrubAuditUrls extends Command
{
    protected $signature = 'audits:scrub-urls {--dry-run : Report how many rows would change, without writing}';

    protected $description = 'Strip query strings (and any credentials in them) from stored audit URLs';

    public function handle(): int
    {
        $affected = DB::table('audits')
            ->where('url', 'like', '%?%')
            ->count();

        if ($this->option('dry-run')) {
            $this->info("Would scrub {$affected} audit URL(s).");

            return self::SUCCESS;
        }

        if ($affected === 0) {
            $this->info('No audit URLs contain a query string.');

            return self::SUCCESS;
        }

        // SUBSTRING_INDEX keeps everything before the first '?'. Done in SQL
        // rather than by loading models: the audits table is append-only and
        // can be very large, and rewriting it row by row through Eloquent
        // would also touch updated_at on records that are meant to be
        // immutable.
        DB::statement("UPDATE audits SET url = SUBSTRING_INDEX(url, '?', 1) WHERE url LIKE '%?%'");

        $this->info("Scrubbed {$affected} audit URL(s).");

        return self::SUCCESS;
    }
}

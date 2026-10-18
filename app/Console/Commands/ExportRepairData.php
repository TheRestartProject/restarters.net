<?php

namespace App\Console\Commands;

use App\Services\RepairDataExport;
use DB;
use Illuminate\Console\Command;

/**
 * Builds the full repair data download, so that the web server can hand it straight out rather than PHP taking a
 * minute or more to build it on every request.
 *
 * Runs hourly, but rebuilds at most once a day, and only if the data has changed.  The hourly run means a fresh
 * machine, which starts without the file, has one again within the hour.
 */
class ExportRepairData extends Command
{
    protected $signature = 'export:repair-data {--force : Rebuild even if built recently or nothing has changed}';

    protected $description = 'Build the repair data download served at /exports/repair-data.csv';

    // Rebuild a little under a day after the last build, so the hourly run doesn't drift later each day.
    private const MAX_AGE_SECONDS = 23 * 3600;

    public static function path(): string
    {
        return public_path('exports/repair-data.csv');
    }

    private function fingerprintPath(): string
    {
        return storage_path('app/exports/repair-data.fingerprint');
    }

    /**
     * Changes when repairs are added, edited or removed, or events or groups change in a way that could change
     * which repairs anyone can see.
     */
    private function fingerprint(): string
    {
        return json_encode([
            DB::table('devices')->count(),
            DB::table('devices')->max('updated_at'),
            DB::table('events')->max('updated_at'),
            DB::table('groups')->max('updated_at'),
        ]);
    }

    public function handle(RepairDataExport $export): int
    {
        $path = self::path();
        $fingerprint = $this->fingerprint();

        if (! $this->option('force') && file_exists($path)) {
            if (time() - filemtime($path) < self::MAX_AGE_SECONDS) {
                $this->info('Built recently; not rebuilding.');

                return self::SUCCESS;
            }

            if (@file_get_contents($this->fingerprintPath()) === $fingerprint) {
                // Mark it as checked, so we don't check again until tomorrow.
                touch($path);
                $this->info('Data unchanged; not rebuilding.');

                return self::SUCCESS;
            }
        }

        foreach ([dirname($path), dirname($this->fingerprintPath())] as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Build alongside and then swap in, so nobody downloads a half-written file.
        $tmp = $path.'.tmp';
        $file = fopen($tmp, 'w');
        $export->write($export->query(), $file);
        fclose($file);
        rename($tmp, $path);

        file_put_contents($this->fingerprintPath(), $fingerprint);
        $this->info('Built '.$path);

        return self::SUCCESS;
    }
}

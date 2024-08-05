<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanOldDiscourseLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discourselogs:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old Discourse log files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $files = glob( storage_path('logs') . DIRECTORY_SEPARATOR . 'discourse-api*');
        $now = Carbon::now();

        foreach ($files as $file) {
            $d = Carbon::createFromTimestamp(filemtime($file));

            if ($now->diffInDays($d) > 14) {
                $this->info("Remove old file $file");
                unlink($file);
            }
        }

        $this->info('Old log files cleaned up successfully.');
    }
}

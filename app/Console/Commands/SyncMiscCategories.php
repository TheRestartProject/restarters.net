<?php

namespace App\Console\Commands;

use App\Misccat;
use Illuminate\Console\Command;

class SyncMiscCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'misccat:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $Misccat = new Misccat;
        $result = $Misccat->updateDevices();
        if ($result) {
            $this->info($result.' rows updated');
        } else {
            $this->info('0 rows updated');
        }
    }
}

<?php

namespace App\Console\Commands;

use App\PrintcatOra;
use Illuminate\Console\Command;

class SyncPrintCatOra extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printcatora:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update devices_printcat_ora table with winning fault types';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Updating devices_printcat_ora with winning fault types');
        $PrintcatOra = new PrintcatOra;
        $result = $PrintcatOra->updateDevices();
        if ($result) {
            $this->info($result.' rows updated');
        } else {
            $this->info('0 rows updated');
        }
    }
}

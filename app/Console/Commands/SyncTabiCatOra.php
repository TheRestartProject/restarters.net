<?php

namespace App\Console\Commands;

use App\TabicatOra;
use Illuminate\Console\Command;

class SyncTabiCatOra extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tabicatora:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update devices_tabicat_ora table with winning fault types';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Updating devices_tabicat_ora with winning fault types');
        $TabicatOra = new TabicatOra;
        $result = $TabicatOra->updateDevices();
        if ($result) {
            $this->info($result.' rows updated');
        } else {
            $this->info('0 rows updated');
        }
    }
}

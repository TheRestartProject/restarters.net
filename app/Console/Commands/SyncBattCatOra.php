<?php

namespace App\Console\Commands;

use App\BattcatOra;
use Illuminate\Console\Command;

class SyncBattCatOra extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'battcatora:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update devices_battcat_ora table with winning fault types';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Updating devices_battcat_ora with winning fault types');
        $BattcatOra = new BattcatOra;
        $result = $BattcatOra->updateDevices();
        if ($result) {
            $this->info($result.' rows updated');
        } else {
            $this->info('0 rows updated');
        }
    }
}

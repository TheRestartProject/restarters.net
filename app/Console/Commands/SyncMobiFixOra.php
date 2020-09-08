<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MobifixOra;

class SyncMobiFixOra extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mobifixora:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update devices_mobifix_ora table with winning fault types';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->info('Updating devices_mobifix_ora with winning fault types');
        $MobifixOra = new MobifixOra;
        $result = $MobifixOra->updateDevices();
        if ($result) {
            $this->info($result . ' rows updated');
        } else {
            $this->info('0 rows updated');
        }
    }

}

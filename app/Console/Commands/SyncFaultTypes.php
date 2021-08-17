<?php

namespace App\Console\Commands;

use App\Faultcat;
use App\Mobifix;
use Illuminate\Console\Command;

class SyncFaultTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faultcat:sync {category : [computers|mobiles]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update devices table with fault types for given categories';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->argument('category');
        switch ($type) {
            case 'computers':
                $this->info('Updating devices with computer fault types');
                $Faultcat = new Faultcat;
                $result = $Faultcat->updateDevices();
                if ($result) {
                    $this->info($result.' rows updated with fault_type');
                } else {
                    $this->info('0 rows updated with fault_type');
                }
                $result = $Faultcat->updateDevicesWithEmptyProblem();
                if ($result) {
                    $this->info($result.' rows updated with Unknown');
                } else {
                    $this->info('0 rows updated with Unknown');
                }
                break;
            case 'mobiles':
                $this->info('Updating devices with mobile fault types');
                $Mobifix = new Mobifix;
                $result = $Mobifix->updateDevices();
                if ($result) {
                    $this->info($result.' rows updated with fault_type');
                } else {
                    $this->info('0 rows updated with fault_type');
                }
                $result = $Mobifix->updateDevicesWithEmptyProblem();
                if ($result) {
                    $this->info($result.' rows updated with Unknown');
                } else {
                    $this->info('0 rows updated with Unknown');
                }
                break;
            default:
                $this->info('Unknown category '.$type);
        }
    }
}

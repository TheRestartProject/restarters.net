<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Faultcat;

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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

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
                    $this->info($result . ' rows updated');
                } else {
                    $this->info('0 rows updated');
                }
                break;
            case 'mobiles':
                $this->info('Updating devices with mobile fault types');
                $Mobifix = new Mobifix;
                $result = $Mobifix->updateDevices();
                if ($result) {
                    $this->info($result . ' rows updated');
                } else {
                    $this->info('0 rows updated');
                }
                break;                
            default: 
                $this->info('Unknown category ' . $type);
        }
        
    }
}

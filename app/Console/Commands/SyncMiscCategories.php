<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Misccat;

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
        $Misccat = new Misccat;
        $result = $Misccat->updateDevices();
        if ($result) {
            $this->info($result . ' rows updated');
        } else {
            $this->info('0 rows updated');
        }
    }
}

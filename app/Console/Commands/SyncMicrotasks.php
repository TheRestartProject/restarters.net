<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncMicrotasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'microtask:sync {--q= : the name of the microtask to synchronise}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update microtask tables with winning opinion';

    /**
     * The microtasks that can be synced.
     *
     * @var array
     */
    protected $livetasks = [
        // 'faultcat' => 'Faultcat',
        // 'misccat' => 'Misccat',
        // 'mobifix' => 'Mobifix',
        // 'mobifixora' => 'MobifixOra',
        // 'printcat' => 'PrintcatOra',
        // 'tabicat' => 'TabicatOra',
        // 'battcat' => 'BattcatOra',
        'dustup' => 'DustupOra',
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $task = strtolower(trim($this->option('q')));
        if (empty($task)) {
            $this->info($this->signature);
        } else if (!array_key_exists($task, $this->livetasks)) {
            $this->info("$task is not a valid microtask or cannot be synced currently");
            $this->info("Valid task names are in " . print_r(array_keys($this->livetasks),1));
        } else {
            $classname = "\\App\\{$this->livetasks[$task]}";
            $this->info("Running $classname updateDevices()");
            $model = new $classname;
            $result = $model->updateDevices();
            if ($result) {
                $this->info($result . ' rows updated');
            } else {
                $this->info('0 rows updated');
            }
        }
    }
}

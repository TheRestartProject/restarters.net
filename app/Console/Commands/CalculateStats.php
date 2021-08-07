<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\FootprintRatioCalculator;
use App\Party;
use App\Device;
use DB;

class CalculateStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcstats:event {--eventid=} {--lcaversion=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate stats and store in `stats_events` table
        calcstats:event
        {--eventid= : event ID | -1 for !! ALL !!}
        {--lcaversion= : LCA version no.}

        * EXPERIMENTAL!
        * Inserts only, like an audit table.
        * Could hold unique record per version though would be prone to accidental update of historic data.
';


    private $_emissionRatio;
    private $_displacement;


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
     * @return void
     */
    public function handle()
    {
        try {
            $idevents = $this->option('eventid');
            $version = $this->option('lcaversion');

            $footprintRatioCalculator = new FootprintRatioCalculator();
            $this->_emissionRatio = $footprintRatioCalculator->calculateRatio();
            $Device = new Device;
            $this->_displacement = $Device->displacement;

            $Party = new Party;

            if ($idevents > 0) {
                $event = $Party->findOrFail($idevents);
                $this->_storeStats($event, $version);
            } else {
                $parties = $Party->getModels(['idevents']);
                foreach ($parties as $event) {
                    $this->_storeStats($event, $version);
                }
            }
        } catch (\Exception $e) {
            print_r("\n\tERROR: " . $e->getMessage() . "\n\n");
        }
    }

    /**
     * @param object $event
     * @param integer $version
     *
     * @return array $eventStats
     */
    private function _storeStats($event, $version)
    {
        try {
            $eventStats = [
                'idevents' => $event->idevents,
                'version' => $version,
                'ratio' => $this->_emissionRatio,
                'displacement' => $this->_displacement,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $stats = $event->getEventStats($this->_emissionRatio);
            // 'participants' often returns NULL
            foreach ($stats as $k => $v) {
                if (is_null($v)) {
                    $stats[$k] = 0;
                }
            }
            $eventStats += $stats;
            DB::table('stats_events')->insert($eventStats);
            return $eventStats;
        } catch (\Exception $e) {
            print_r("\n\tERROR: " . $e->getMessage() . "\n\n");
            return [];
        }
    }
}

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
    protected $signature = 'calcstats:event {--eventid=0} {--eventdate=} {--lcaversion=} {--ratios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '
        calcstats:event
        {--eventid=0 : event ID | 0 for ALL by eventdate}
        {--eventdate= : yyyy-mm-dd ~ if eventid=0 then events up to and including this date, ignored if eventid>0}
        {--lcaversion= : LCA version no.}
        {--ratios : ignore other options, just print historic emission ratios}

        * EXPERIMENTAL!
        * Inserts only, like an audit table.
        * Could hold unique record per version though would be prone to accidental update of historic data.
        * Outputs results to:
        *   storage/logs/stats-[idevents]-[version]-[eventdate].csv"
        *   storage/logs/stats-[idevents]-[version]-[eventdate].json"
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

            $ratios = $this->option('ratios');
            if ($ratios) {
                $this->_historicRatios();
                return;
            }

            $version = $this->option('lcaversion');
            $idevents = $this->option('eventid');
            $eventdate = $this->option('eventdate');

            $footprintRatioCalculator = new FootprintRatioCalculator();
            $this->_emissionRatio = $footprintRatioCalculator->calculateRatio();
            $Device = new Device;
            $this->_displacement = $Device->displacement;

            $stats = [];
            if ($idevents > 0) {
                $Party = new Party;
                $event = $Party->findOrFail($idevents);
                $stats = $this->_compile($event, $version, $this->_emissionRatio);
                if ($stats['fixed_devices'] > 0) {
                    $this->_toFile([$stats], $version, $idevents);
                } else {
                    print_r("\n\tNO STATS FOR EVENT: " . $idevents . "\n\n");
                }
            } else {
                if (!$eventdate or !preg_match('/20[0-9]{2}-[0-1][0-9]-[0-3][0-9]/', $eventdate)) {
                    $eventdate = date('Y-m-d', time());
                }
                $parties = Party::where([['events.event_date', '<=', $eventdate], ['events.event_date', '>', '2000-01-01']])->get();
                $result = [];
                foreach ($parties as $event) {
                    $device = Device::select('created_at')
                        ->where('event', $event->idevents)
                        ->orderBy('created_at', 'desc')
                        ->limit(1)
                        ->first();
                    if ($device->created_at) {
                        $statsNow = $event->getEventStats($this->_emissionRatio);
                        if ($statsNow['fixed_devices'] > 0) {
                            $stats = [
                                'version' => $version,
                                'idevents' => $event->idevents,
                                'idgroups' => $event->group,
                                'event_date' => $event->event_date,
                                'displacement' => $this->_displacement,
                                'fixed_powered' => $statsNow['fixed_powered'],
                                'fixed_unpowered' => $statsNow['fixed_unpowered'],
                                'ratio_now' => $this->_emissionRatio,
                                'ratio_now_date' => date('Y-m-d'),
                                'co2_now' => $statsNow['co2'],
                                'ewaste_now' => $statsNow['ewaste'],
                                'unpowered_waste_now' => $statsNow['unpowered_waste'],
                            ];

                            $emission_ratio = $this->_calculateRatio(substr($device->created_at, 0, 10));
                            $statsThen = $event->getEventStats($emission_ratio);
                            $stats += [
                                'ratio_then' => $emission_ratio,
                                'ratio_then_date' => substr($device->created_at, 0, 10),
                                'co2_then' => $statsThen['co2'],
                                'ewaste_then' => $statsThen['ewaste'],
                                'unpowered_waste_then' => $statsThen['unpowered_waste'],
                                'stats_compiled' => date('Y-m-d'),
                            ];
                            $result[] = $stats;
                            if ($statsNow['co2'] !== $statsThen['co2']) {
                                logger(print_r($stats, 1));
                            }
                        }
                    }
                }
                $this->_toFile($result, $version, $idevents, $eventdate);
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
    private function _compile($event, $version, $emission_ratio)
    {
        try {
            $eventStats = [
                'idevents' => $event->idevents,
                'idgroups' => $event->group,
                'event_date' => $event->event_date,
                'version' => $version,
                'ratio' => $emission_ratio,
                'displacement' => $this->_displacement,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $stats = $event->getEventStats($emission_ratio);
            // 'participants' often returns NULL
            foreach ($stats as $k => $v) {
                if (is_null($v)) {
                    $stats[$k] = 0;
                }
            }
            $eventStats += $stats;
            return $eventStats;
        } catch (\Exception $e) {
            print_r("\n\tERROR: " . $e->getMessage() . "\n\n");
            return [];
        }
    }

    /**
     * Prior to 2013-06-09 the only devices in the table were all category 46 with estimates
     * Emission ratios for these events at the time = NULL or 0
     * Stats for these events use emission ratios calculated with subsequent device weights
     * Stats calculated at the time would have return 0 co2 vals
SELECT
d.`event`,
e.`event_date`,
d.`category`,
d.`repair_status`,
d.`estimate`,
d.`created_at`
FROM `devices` d
JOIN `events` e ON e.`idevents` = d.`event`
WHERE `e`.`event_date` <= '2013-06-08'
ORDER BY e.`event_date` ASC
     */
    private function _historicRatios()
    {
        $parties = Party::select('idevents', 'event_date')
            ->where('event_date', '>', '2013-06-08')
            ->distinct()->orderBy('event_date', 'asc')
            ->get();
        foreach ($parties as $k => $event) {
            $device = Device::select('created_at')
                ->where('event', $event->idevents)
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->first();
            if ($device->created_at) {
                $ratios[$k]['idevents'] = $event->idevents;
                $ratios[$k]['event_date'] = $event->event_date;
                $ratios[$k]['devices_date'] = substr($device->created_at, 0, 10);
                $ratios[$k]['emission_ratio'] = $this->_calculateRatio(substr($device->created_at, 0, 10));
            }
        }
        $file = storage_path() . "/logs/stats_emission_ratio_history.csv";
        file_put_contents($file, $this->_strPutCsv($ratios));
    }

    private function _calculateRatio($date)
    {
        $result = DB::select(DB::raw("
SELECT sum(`c`.`footprint`) / sum(`c`.`weight` + 0.0) AS ratio
FROM `devices` d
JOIN `categories` c ON  `c`.`idcategories` = `d`.`category`
JOIN `events` e ON  `e`.`idevents` = `d`.`event`
WHERE `d`.`repair_status` = 1
AND `d`.`created_at` <= '$date'
AND `c`.`idcategories` != 46
"));
        return $result[0]->ratio;
    }

    /**
     * @param array $eventStats
     *
     * @return void
     */
    private function _toDbTable($stats)
    {
        try {
            foreach ($stats as $eventStats) {
                DB::table('stats_events')->insert($eventStats);
            }
        } catch (\Exception $e) {
            print_r("\n\tERROR: " . $e->getMessage() . "\n\n");
        }
    }

    private function _toFile($stats, $version, $idevents = 0, $eventdate = NULL)
    {
        $file = storage_path() . "/logs/stats_v$version";
        $file .= $idevents ? "_$idevents" : '';
        $file .= $eventdate ? "_$eventdate" : '';
        file_put_contents("$file.json", json_encode($stats, JSON_PRETTY_PRINT));
        file_put_contents("$file.csv", $this->_strPutCsv($stats));
    }

    private function _strPutCsv($data)
    {
        try {
            $fh = fopen('php://temp', 'rw');
            fputcsv($fh, array_keys(current($data)));
            foreach ($data as $row) {
                fputcsv($fh, $row);
            }
            rewind($fh);
            $csv = stream_get_contents($fh);
            fclose($fh);
            return $csv;
        } catch (\Exception $exc) {
            print_r("\n\EXCEPTION: " . $exc->getMessage() . "\n\n");
        }
    }
}

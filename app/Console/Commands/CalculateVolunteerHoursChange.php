<?php

namespace App\Console\Commands;

use App\Group;
use Illuminate\Console\Command;
use DB;

class CalculateVolunteerHoursChange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:volunteerhourschange';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Temp command for upcoming stats change';

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
        echo("GroupID,GroupName,EventDurationDiff,EventDurationTotal,EventDurationPercentChange,EventCount,VolunteerHoursOld,VolunteerHoursNew,VolunteerHoursDiff,VolunteerHoursDiffPercent\n");
        $groups = DB::Select(
            DB::raw(
                "SELECT `group`, name, diff, total, 100 * diff / total AS percent, count FROM ( SELECT `group`, 
              -- Event length 
              SUM(TIMESTAMPDIFF(HOUR, event_start_utc, event_end_utc)) AS diff, 
              -- Total event length for this group 
              (SELECT SUM(TIMESTAMPDIFF(HOUR, e2.event_start_utc, e2.event_end_utc)) FROM events e2 WHERE e2.group = events.group) AS total,
              -- Number of events 
              COUNT(DISTINCT(idevents)) AS count FROM events 
              -- Shorter than currently assumed 3 hours 
              WHERE TIMESTAMPDIFF(HOUR, event_start_utc, event_end_utc) < 3 
              -- Filter out groups with few events 
              GROUP BY `group` HAVING count > 2 ORDER BY diff DESC ) t INNER JOIN groups ON t.group = groups.idgroups ORDER BY diff DESC"
            )
        );

        $stats = [];

        foreach ($groups as $group)
        {
            $g = Group::find($group->group);
            $old = $g->getGroupStats(null, null, false);
            $new = $g->getGroupStats(null, null, true);

            if ($old['hours_volunteered'] != $new['hours_volunteered'])
            {
                $stats[] = [
                    $group->group,
                    $group->name,
                    $group->diff,
                    $group->total,
                    round($group->percent),
                    $group->count,
                    $old['hours_volunteered'],
                    $new['hours_volunteered'],
                    ($new['hours_volunteered'] - $old['hours_volunteered']),
                    round((100 * ($new['hours_volunteered'] - $old['hours_volunteered']) / $new['hours_volunteered']))
                ];
            }
        }

        // Sort group by largest percentage change in volunter hours
        usort($stats, function($a, $b) {
            return abs($b[9]) <=> abs($a[9]);
        });

        foreach ($stats as $stat)
        {
            echo(implode(',', $stat) . "\n");
        }

    }
}

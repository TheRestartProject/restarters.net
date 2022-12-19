<?php

namespace App;

use App\Device;
use App\Helpers\Fixometer;
use DB;
use Illuminate\Database\Eloquent\Model;

class Search extends Model
{
    public function parties($list = [], $groups = [], $from = null, $to = null, $group_tags = null, $allowedParties = null)
    {
        $eventsQuery = Party::past()
                     ->leftJoin('grouptags_groups as gtag', 'events.group', 'gtag.group');

        if (! empty($list)) {
            $eventsQuery->whereIn('events.idevents', $list);
        }

        if (! is_null($groups)) {
            $eventsQuery->whereIn('events.group', $groups);
        }

        if (! is_null($from)) {
            $eventsQuery->where('event_start_utc', '>=', date('Y-m-d H:i:s', $from));
        }

        if (! is_null($to)) {
            $eventsQuery->where('event_end_utc', '<=', date('Y-m-d H:i:s', $to));
        }

        if (! is_null($group_tags)) {
            $eventsQuery->whereIn('gtag.group_tag', $group_tags);
        }

        if (! is_null($allowedParties)) {
            $eventsQuery->whereIn('events.idevents', $allowedParties);
        }

        $eventsQuery->groupBy('events.idevents');
        $eventsQuery->orderBy('events.event_start_utc', 'desc');

        // We need to explicitly select what we want to return otherwise gtag.group might overwrite events.group.
        $events = $eventsQuery->select(['events.*', 'gtag.group_tag'])->get();

        // We need to filter based on approved visibility:
        // - where the group is approved, this event is visible
        // - where the group is not approved, this event is visible to network coordinators or group hosts.
        $me = auth()->user();
        $amHost = $me->hasRole('Host');
        $admin = $me->hasRole('Administrator');

        $events = $events->filter(function ($event) use ($me, $amHost, $admin) {
            $group = Group::find($event['group']);

            if ($group->approved || $admin || $me->isCoordinatorForGroup($group) || $amHost && Fixometer::userIsHostOfGroup($group->idgroups, $me->id)) {
                return true;
            }

            return false;
        });

        return $events;
    }

    public function deviceStatusCount($parties)
    {
        $sql = 'SELECT COUNT(*) AS `counter`, `d`.`repair_status` AS `status`, `d`.`event`
                FROM `devices` AS `d`';
        $sql .= ' WHERE `repair_status` > 0 ';
        $sql .= ' AND `d`.`event` IN ('.implode(', ', $parties).')';
        $sql .= ' GROUP BY `status`';

        return DB::select(DB::raw($sql));
    }

    public function countByCluster($parties, $cluster)
    {
        $sql = 'SELECT COUNT(*) AS `counter`, `repair_status` FROM `devices` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE `c`.`cluster` = :cluster AND `d`.`repair_status` > 0 ';
        $sql .= ' AND `d`.`event` IN ('.implode(', ', $parties).') ';
        $sql .= '  GROUP BY `repair_status`
                  ORDER BY `repair_status` ASC
                ';
        try {
            return DB::select(DB::raw($sql), ['cluster' => $cluster]);
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }
    }

    public function findMostSeen($parties, $status = 1, $cluster = null)
    {
        $m1 = env('MISC_CATEGORY_ID');
        $parties = implode(', ', $parties);
        $sql = "SELECT COUNT(`d`.`category`) AS `counter`, `c`.`name` FROM `devices` AS `d`
                INNER JOIN `events` AS `e` ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c` ON `d`.`category` = `c`.`idcategories`
                WHERE 1=1 and `c`.`idcategories` NOT IN ($m1)
                AND `d`.`event` IN ($parties)";
        if (! is_null($status) && is_numeric($status)) {
            $sql .= ' AND `d`.`repair_status` = :status ';
        }
        if (! is_null($cluster) && is_numeric($cluster)) {
            $sql .= ' AND `c`.`cluster` = :cluster ';
        }
        $sql .= ' GROUP BY `d`.`category` ORDER BY `counter` DESC';
        $sql .= (! is_null($cluster) ? '  LIMIT 1' : '');

        try {
            if (! is_null($status) && is_numeric($status) && is_null($cluster)) {
                return DB::select(DB::raw($sql), ['status' => $status]);
            } elseif (! is_null($cluster) && is_numeric($cluster) && is_null($status)) {
                return DB::select(DB::raw($sql), ['cluster' => $cluster]);
            } elseif (! is_null($status) && is_numeric($status) && ! is_null($cluster) && is_numeric($cluster)) {
                return DB::select(DB::raw($sql), ['status' => $status, 'cluster' => $cluster]);
            } else {
                return DB::select(DB::raw($sql));
            }
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }
    }
}

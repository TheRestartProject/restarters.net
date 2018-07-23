<?php

namespace App;

use App\Device;

use Illuminate\Database\Eloquent\Model;

use DB;

class Search extends Model
{
    public function parties($list = array(), $groups = array(), $from = null, $to = null, $group_tags = null, $allowedParties = null)
    {
        $eventsQuery = Party::pastEvents()
                     ->with('devices.deviceCategory')
                     ->leftJoin('grouptags_groups as gtag', 'events.group', 'gtag.group');

        if (!empty($list)) {
            $eventsQuery->whereIn('events.idevents', $list);
        }

        if (!is_null($groups)) {
            $eventsQuery->whereIn('events.group', $groups);
        }

        if (!is_null($from)) {
            $eventsQuery->whereRaw('UNIX_TIMESTAMP(event_date) >= ' . $from);
        }

        if (!is_null($to)) {
            $eventsQuery->whereRaw('UNIX_TIMESTAMP(event_date) <= ' . $to);
        }

        if (!is_null($group_tags)) {
            $eventsQuery->whereIn('gtag.group_tag', $group_tags);
        }

        if (!is_null($allowedParties)) {
            $eventsQuery->whereIn('events.idevents', $allowedParties);
        }

        $eventsQuery->groupBy('events.idevents');
        $eventsQuery->orderBy('events.event_date', 'desc');


        $parties = $eventsQuery->get();

        return $parties;
    }

    public function deviceStatusCount($parties){
        $sql = 'SELECT COUNT(*) AS `counter`, `d`.`repair_status` AS `status`, `d`.`event`
                FROM `devices` AS `d`';
        $sql .= ' WHERE `repair_status` > 0 ';
        $sql .= ' AND `d`.`event` IN (' . implode( ', ', $parties ). ')';
        $sql .= ' GROUP BY `status`';

        return DB::select(DB::raw($sql));
    }

    public function countByCluster($parties, $cluster){
        $sql = 'SELECT COUNT(*) AS `counter`, `repair_status` FROM `devices` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE `c`.`cluster` = :cluster AND `d`.`repair_status` > 0 ';
        $sql .= ' AND `d`.`event` IN (' . implode( ', ', $parties ) . ') ';
        $sql.= '  GROUP BY `repair_status`
                  ORDER BY `repair_status` ASC
                ';
        try {
          return DB::select(DB::raw($sql), array('cluster' => $cluster));
        } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
        }

    }

    public function findMostSeen($parties, $status = 1, $cluster = null ){
        $sql = 'SELECT COUNT(`d`.`category`) AS `counter`, `c`.`name` FROM `devices` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE 1=1 and `c`.`idcategories` <> ' . env('MISC_CATEGORY_ID');
        $sql .= ' AND `d`.`event` IN (' . implode( ', ', $parties ) . ')';


        if(!is_null($status) && is_numeric($status)){
            $sql .= ' AND `d`.`repair_status` = :status ';
        }
        if(!is_null($cluster) && is_numeric($cluster)){
            $sql .= ' AND `c`.`cluster` = :cluster ';
        }

        $sql.= ' GROUP BY `d`.`category`
                ORDER BY `counter` DESC';
        $sql .= (!is_null($cluster) ? '  LIMIT 1' : '');

        try {
          if(!is_null($status) && is_numeric($status) && is_null($cluster)){
              return DB::select(DB::raw($sql), array('status' => $status));
          } elseif(!is_null($cluster) && is_numeric($cluster) && is_null($status)){
              return DB::select(DB::raw($sql), array('cluster' => $cluster));
          } elseif(!is_null($status) && is_numeric($status) && !is_null($cluster) && is_numeric($cluster)) {
              return DB::select(DB::raw($sql), array('status' => $status, 'cluster' => $cluster));
          } else {
              return DB::select(DB::raw($sql));
          }
        } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
        }

    }


}

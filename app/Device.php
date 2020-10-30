<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Device extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'devices';
    public $displacement = 0.5;
    protected $primaryKey = 'iddevices';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['event', 'category', 'category_creation', 'estimate', 'repair_status', 'spare_parts', 'parts_provider', 'brand', 'item_type', 'model', 'age', 'problem', 'notes', 'repaired_by', 'do_it_yourself', 'professional_help', 'more_time_needed', 'wiki', 'fault_type'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public static function boot()
    {
        parent::boot();

        static::deleting(function($device) {
            $device->barriers()->detach();
            $device->urls()->delete();
        });
    }

    // Setters

    //Getters
    public function getList($params = null)
    {
        //Tested!
        $sql = 'SELECT * FROM `view_devices_list`';

        if ( ! is_null($params)) {
            $sql .= ' WHERE 1=1 AND ';

            $params = array_filter($params);
            foreach ($params as $field => $value) {
                if ($field == 'brand' || $field == 'model' || $field == 'problem') {
                    $params[$field] = '%'.strtolower($value).'%';
                } elseif ($field == 'event_date') {
                    $params[$field] = implode(' AND ', $value);
                }
            }

            $clauses = array();

            foreach ($params as $f => $v) {
                if ($f == 'event_date') {
                    $clauses[] = 'event_date BETWEEN '.$v;
                }
                if ($f == 'category' || $f == 'group') {
                    $clauses[] = 'id'.$f.' IN ('.$v.')';
                } elseif ($f == 'brand' || $f == 'model' || $f == 'problem') {
                    $clauses[] = $f.' LIKE :'.$f;
                }
            }

            $sql .= implode(' AND ', $clauses);
        }

        $sql .= ' ORDER BY `sorter` DESC';

        if ( ! empty($params) && array_key_exists('event_date', $params)) {
            unset($params['event_date']);
        }

        if ($params != null) {
            return DB::select(DB::raw($sql), $params);
        }

        return DB::select(DB::raw($sql));
    }

    public function getWeights($group = null)
    {
        $sql =
        'SELECT

sum(case when (devices.category = 46) then (devices.estimate + 0.0) else categories.weight end) as `total_weights`,

sum(case when (devices.category = 46) then (devices.estimate + 0.0) * @ratio else (categories.footprint * @displacement) end) as `total_footprints`

FROM devices, categories, events,

(select @displacement := :displacement) inner_tbl_displacement,

(select @ratio := ((sum(`categories`.`footprint`) * :displacement1) / sum(`categories`.`weight` + 0.0)) from `devices`, `categories` where `categories`.`idcategories` = `devices`.`category` and `devices`.`repair_status` = 1 and categories.idcategories != 46
) inner_tbl_ratio

WHERE devices.category = categories.idcategories and devices.repair_status = 1
AND devices.event = events.idevents ';

        // Using two named parameters for displacement due to restriction of Laravel/MySQL.
        // see e.g.: https://github.com/laravel/framework/issues/12715
        $params = ['displacement' => $this->displacement, 'displacement1' => $this->displacement];

        if ( ! is_null($group) && is_numeric($group)) {
            $sql .= ' AND events.group = :group ';
            $params['group'] = $group;

            return DB::select(DB::raw($sql), $params);
        }

        return DB::select(DB::raw($sql), $params);
    }

    public function getPartyWeights($party)
    {
        return DB::select(DB::raw('SELECT
                ROUND(SUM(`weight`), 0) + ROUND(SUM(`estimate`), 0) AS `total_weights`,
                ROUND(SUM(`footprint`) * '.$this->displacement.', 0) + (ROUND(SUM(`estimate`) * (SELECT * FROM `view_waste_emission_ratio`), 0))  AS `total_footprints`
            FROM `'.$this->table.'` AS `d`
            INNER JOIN `categories` AS `c` ON  `d`.`category` = `c`.`idcategories`
            INNER JOIN `events` AS `e` ON  `d`.`event` = `e`.`idevents`
            WHERE `d`.`repair_status` = 1 AND `c`.`idcategories` != 46 AND `e`.`idevents` = :id'), array('id' => $party));
    }

    public function getCounts()
    {
        return DB::select(DB::raw('SELECT
                    COUNT(`category`) AS `catcount`,
                    ROUND(SUM(`weight`), 2) AS `catcount_weight`,
                    `name`
                FROM `'.$this->table.'` AS `d`
                INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`
                WHERE `d`.`repair_status` = 1
                GROUP BY `category`
                ORDER BY `catcount` DESC'));
    }

    public function getByYears($repair_status)
    {
        return DB::select(DB::raw('SELECT
                    COUNT(`iddevices`) AS `total_devices`,
                    YEAR(`event_date`) AS `event_year`
                FROM `'.$this->table.'` AS `d`
                INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event`
                WHERE `d`.`repair_status` = :rp
                GROUP BY `event_year`
                ORDER BY `event_year` ASC'), array('rp' => $repair_status));
    }

    public function ofThisUser($id)
    {
        //Tested
        return DB::select(DB::raw('SELECT * FROM `'.$this->table.'` WHERE `repaired_by` = :id'), array('id' => $id));
    }

    public function ofThisEvent($event)
    {
        //Tested
        return DB::select(DB::raw('SELECT * FROM `'.$this->table.'` AS `d`
                INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`
                LEFT JOIN (
                  SELECT * FROM xref
                    INNER JOIN images ON images.idimages = xref.object
                    WHERE object_type = '.env('TBL_IMAGES').' AND reference_type = '.env('TBL_DEVICES').'
                  ) AS i ON i.reference = d.iddevices

                WHERE `event` = :event'), array('event' => $event));
    }

    public function ofThisGroup($group)
    {
        //Tested
        return DB::select(DB::raw('SELECT * FROM `'.$this->table.'` AS `d`
                INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`
                INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event`
                WHERE `group` = :group'), array('group' => $group));
    }

    public function ofAllGroups()
    {
        //Tested
        return DB::select(DB::raw('SELECT * FROM `'.$this->table.'` AS `d`
                INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`
                INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event`'));
    }

    public function statusCount($g = null, $year = null)
    {
        $sql = 'SELECT COUNT(*) AS `counter`, `d`.`repair_status` AS `status`, `d`.`event`
                FROM `'.$this->table.'` AS `d`';
        if (( ! is_null($g) && is_numeric($g)) || ( ! is_null($year) && is_numeric($year))) {
            $sql .= ' INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event` ';
        }

        $sql .= ' WHERE `repair_status` > 0 ';

        if ( ! is_null($g) && is_numeric($g)) {
            $sql .= ' AND `group` = :g ';
        }
        if ( ! is_null($year) && is_numeric($year)) {
            $sql .= ' AND YEAR(`event_date`) = :year ';
        }

        $sql .= ' GROUP BY `status`';

        if ( ! is_null($year) && is_numeric($year)) {
            $sql .= ', `event`';
        }

        if ( ! is_null($g) && is_numeric($g) && is_null($year)) {
            return DB::select(DB::raw($sql), array('g' => $g));
        } elseif ( ! is_null($year) && is_numeric($year) && is_null($g)) {
            return DB::select(DB::raw($sql), array('year' => $year));
        } elseif ( ! is_null($year) && is_numeric($year) && ! is_null($g) && is_numeric($g)) {
            return DB::select(DB::raw($sql), array('year' => $year, 'g' => $g));
        }

        return DB::select(DB::raw($sql));
    }

    public function partyStatusCount($event)
    {
        $sql = 'SELECT COUNT(*) AS `counter`, `d`.`repair_status` AS `status`, `d`.`event`
                FROM `'.$this->table.'` AS `d`';

        $sql .= ' WHERE `repair_status` > 0 ';

        $sql .= ' GROUP BY `status`';

        if ( ! is_null($event) && is_numeric($event)) {
            $sql .= ' AND `event` = :event ';
        }

        return DB::select(DB::raw($sql), array('event' => $event));
    }

    public function countByCluster($cluster, $group = null, $year = null)
    {
        $sql = 'SELECT COUNT(*) AS `counter`, `repair_status` FROM `'.$this->table.'` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE `c`.`cluster` = :cluster AND `d`.`repair_status` > 0 ';

        if ( ! is_null($group)) {
            $sql .= ' AND `e`.`group` = :group ';
        }
        if ( ! is_null($year)) {
            $sql .= ' AND YEAR(`e`.`event_date`) = :year ';
        }

        $sql .= ' GROUP BY `repair_status`
                ORDER BY `repair_status` ASC
                ';

        try {
            if ( ! is_null($group) && is_numeric($group) && is_null($year)) {
                return DB::select(DB::raw($sql), array('group' => $group, 'cluster' => $cluster));
            } elseif ( ! is_null($year) && is_numeric($year) && is_null($group)) {
                return DB::select(DB::raw($sql), array('year' => $year, 'cluster' => $cluster));
            } elseif ( ! is_null($year) && is_numeric($year) && ! is_null($group) && is_numeric($group)) {
                return DB::select(DB::raw($sql), array('year' => $year, 'group' => $group, 'cluster' => $cluster));
            }

            return DB::select(DB::raw($sql), array('cluster' => $cluster));
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }
    }

    public function countCO2ByYear($group = null, $year = null)
    {
        $sql = 'SELECT
                    (ROUND(SUM(`c`.`footprint`), 0) * '.$this->displacement.') + ( CASE WHEN `d`.`category` = 46 THEN IFNULL(ROUND(SUM(`estimate`) * (SELECT * FROM `view_waste_emission_ratio`), 0),0) ELSE 0 END) AS `co2`,
                    YEAR(`e`.`event_date`) AS `year`
                FROM `'.$this->table.'` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE `d`.`repair_status` = 1 ';

        if ( ! is_null($group)) {
            $sql .= ' AND `e`.`group` = :group ';
        }
        if ( ! is_null($year)) {
            $sql .= ' AND YEAR(`e`.`event_date`) = :year ';
        }
        $sql .= ' GROUP BY `year`
                ORDER BY `year` DESC'; // was grouped by category too at some point

        try {
            if ( ! is_null($group) && is_numeric($group) && is_null($year)) {
                return DB::select(DB::raw($sql), array('group' => $group));
            } elseif ( ! is_null($year) && is_numeric($year) && is_null($group)) {
                return DB::select(DB::raw($sql), array('year' => $year));
            } elseif ( ! is_null($year) && is_numeric($year) && ! is_null($group) && is_numeric($group)) {
                return DB::select(DB::raw($sql), array('year' => $year, 'group' => $group));
            }

            return DB::select(DB::raw($sql));
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }
    }

    public function countWasteByYear($group = null, $year = null)
    {
        $sql = 'SELECT
                    ROUND(SUM(`c`.`weight`), 0) + IFNULL( ROUND(SUM(`d`.`estimate`), 0), 0) AS `waste`,
                    YEAR(`e`.`event_date`) AS `year`
                FROM `'.$this->table.'` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE `d`.`repair_status` = 1 ';

        if ( ! is_null($group)) {
            $sql .= ' AND `e`.`group` = :group ';
        }
        if ( ! is_null($year)) {
            $sql .= ' AND YEAR(`e`.`event_date`) = :year ';
        }
        $sql .= ' GROUP BY `year`
                ORDER BY `year` DESC';

        try {
            if ( ! is_null($group) && is_numeric($group) && is_null($year)) {
                return DB::select(DB::raw($sql), array('group' => $group));
            } elseif ( ! is_null($year) && is_numeric($year) && is_null($group)) {
                return DB::select(DB::raw($sql), array('year' => $year));
            } elseif ( ! is_null($year) && is_numeric($year) && ! is_null($group) && is_numeric($group)) {
                return DB::select(DB::raw($sql), array('year' => $year, 'group' => $group));
            }

            return DB::select(DB::raw($sql));
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }
    }

    public function findMostSeen($status = null, $cluster = null, $group = null)
    {
        $sql = 'SELECT COUNT(`d`.`category`) AS `counter`, `c`.`name` FROM `'.$this->table.'` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE 1=1 and `c`.`idcategories` <> '.env('MISC_CATEGORY_ID');

        if ( ! is_null($status) && is_numeric($status)) {
            $sql .= ' AND `d`.`repair_status` = :status ';
        }
        if ( ! is_null($cluster) && is_numeric($cluster)) {
            $sql .= ' AND `c`.`cluster` = :cluster ';
        }
        if ( ! is_null($group) && is_numeric($group)) {
            $sql .= ' AND `e`.`group` = :group ';
        }

        $sql .= ' GROUP BY `d`.`category`
                 ORDER BY `counter` DESC';

        $sql .= ( ! is_null($cluster) ? '  LIMIT 1' : '');

        if ( ! is_null($cluster) && is_numeric($cluster)) {
            try {
                if ( ! is_null($group) && is_numeric($group) && is_null($status)) {
                    return DB::select(DB::raw($sql), array('group' => $group, 'cluster' => $cluster));
                } elseif ( ! is_null($status) && is_numeric($status) && is_null($group)) {
                    return DB::select(DB::raw($sql), array('status' => $status, 'cluster' => $cluster));
                } elseif ( ! is_null($status) && is_numeric($status) && ! is_null($group) && is_numeric($group)) {
                    return DB::select(DB::raw($sql), array('status' => $status, 'group' => $group, 'cluster' => $cluster));
                }

                return DB::select(DB::raw($sql), array('cluster' => $cluster));
            } catch (\Illuminate\Database\QueryException $e) {
                dd($e);
            }
        } else {
            try {
                if ( ! is_null($group) && is_numeric($group) && is_null($status)) {
                    return DB::select(DB::raw($sql), array('group' => $group));
                } elseif ( ! is_null($status) && is_numeric($status) && is_null($group)) {
                    return DB::select(DB::raw($sql), array('status' => $status));
                } elseif ( ! is_null($status) && is_numeric($status) && ! is_null($group) && is_numeric($group)) {
                    return DB::select(DB::raw($sql), array('status' => $status, 'group' => $group));
                }

                return DB::select(DB::raw($sql));
            } catch (\Illuminate\Database\QueryException $e) {
                dd($e);
            }
        }
    }

    public function successRates($cluster = null, $direction = 'DESC', $threshold = 10)
    {
        $sql = 'SELECT
                        COUNT(repair_status) AS fixed,
                        total_devices,
                        categories.name AS category_name,
                        clusters.name AS cluster_name,
                        ROUND( (COUNT(repair_status) * 100 / total_devices), 1) AS success_rate ';
        if ( ! is_null($cluster)) {
            $sql .= ', clusters.idclusters AS cluster ';
        }

        $sql .= ' FROM devices
                        INNER JOIN categories ON categories.idcategories = devices.category
                        INNER JOIN (
                            SELECT
                                COUNT(iddevices) AS total_devices,
                                devices.category
                            FROM devices
                            GROUP BY devices.category
                            ) AS totals ON totals.category = devices.category
                        INNER JOIN clusters ON clusters.idclusters = categories.cluster ';

        $sql .= 'WHERE
                        repair_status = 1 AND
                        total_devices > '.$threshold.' ';

        if ( ! is_null($cluster)) {
            $sql .= ' AND cluster = :cluster ';
        }
        $sql .= 'GROUP BY devices.category
                    ORDER BY cluster ASC, success_rate '.$direction.' LIMIT 1';

        if ( ! is_null($cluster)) {
            return DB::select(DB::raw($sql), array('cluster' => $cluster));
        }

        return DB::select(DB::raw($sql));
    }

    public function guesstimates()
    {
        //Tested
        return DB::select(DB::raw('SELECT COUNT(*) AS guesstimates FROM `'.$this->table.'` WHERE `category` = 46'));
    }

    public function export()
    {
        //Tested
        return DB::select(DB::raw('SELECT
                    `c`.`name` AS `category`,
                    `brand`,
                    `model`,
                    `problem`,
                    `repair_status`,
                    `spare_parts`,
                    `e`.`location`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`,
                    `g`.`name` AS `group_name`

                FROM `devices` AS `d`
                INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`
                INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event`
                INNER JOIN `groups` AS `g` ON `g`.`idgroups` = `e`.`group`'));
    }

    public function findOne($id)
    {
        return $this->where('iddevices', $id)->first();
    }

    public function howMany($params = null)
    {
        if (empty($params)) {
            return count(self::all());
        }

        return count(self::where($params));
    }

    public function deviceCategory()
    {
        return $this->hasOne('App\Category', 'idcategories', 'category');
    }

    public function deviceEvent()
    {
        return $this->hasOne('App\Party', 'idevents', 'event');
    }

    public function urls()
    {
        return $this->hasMany('App\DeviceUrl', 'device_id', 'iddevices');
    }

    public function barriers()
    {
        return $this->belongsToMany('App\Barrier', 'devices_barriers', 'device_id', 'barrier_id');
    }

    public function co2Diverted($emissionRatio, $displacementFactor)
    {
        $footprint = 0;

        if ($this->isFixed()) {
            if ($this->deviceCategory->isMisc()) {
                if (is_numeric($this->estimate)) {
                    $footprint = $this->estimate * $emissionRatio;
                }
            } else {
                $footprint = (float) $this->deviceCategory->footprint;
            }
        }

        return $footprint * $displacementFactor;
    }

    public function ewasteDiverted()
    {
        $ewasteDiverted = 0;

        if ($this->isFixed()) {
            if ($this->deviceCategory->isMisc()) {
                if (is_numeric($this->estimate)) {
                    $ewasteDiverted = $this->estimate;
                }
            } else {
                $ewasteDiverted = (float) $this->deviceCategory->weight;
            }
        }

        return $ewasteDiverted;
    }

    public function unpoweredWasteDiverted()
    {
        $wasteDiverted = 0;

        if ($this->isFixed()) {
            if (is_numeric($this->estimate)) {
                $wasteDiverted = $this->estimate;
            } else {
                $wasteDiverted = (float) $this->deviceCategory->weight;
            }
        }

        return $wasteDiverted;
    }

    public function isFixed()
    {
        return $this->repair_status == env('DEVICE_FIXED');
    }

    public function getProblem()
    {
        if ( ! empty($this->problem)) {
            return $this->problem;
        }

        return 'N/A';
    }

    public function getRepairStatus()
    {
        if ($this->repair_status == 1) {
            return trans('partials.fixed');
        } elseif ($this->repair_status == 2) {
            return trans('partials.repairable');
        } elseif ($this->repair_status == 3) {
            return trans('partials.end_of_life');
        }

        return 'N/A';
    }

    public function getNextSteps()
    {
        if ($this->more_time_needed == 1) {
            return trans('partials.more_time');
        } elseif ($this->professional_help == 1) {
            return trans('partials.professional_help');
        } elseif ($this->do_it_yourself == 1) {
            return trans('partials.diy');
        }

        return null;
    }

    public function getSpareParts()
    {
        if ($this->parts_provider == 2) {
            return trans('partials.yes_third_party');
        } elseif ($this->spare_parts == 1 && ! is_null($this->parts_provider)) {
            return trans('partials.yes_manufacturer');
        } elseif ($this->spare_parts == 2) {
            return trans('partials.no');
        }

        return null;
    }

    public function getAge()
    {
        if ( ! empty($this->age)) {
            return $this->age;
        }

        return '-';
    }

    public function getShortProblem($length = 60)
    {
        return str_limit($this->problem, $length);
    }

    public function getImages()
    {
        $File = new \FixometerFile;

        return $File->findImages(env('TBL_DEVICES'), $this->iddevices);
    }
}

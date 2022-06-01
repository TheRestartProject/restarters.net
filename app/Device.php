<?php

namespace App;

use App\Events\DeviceCreatedOrUpdated;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;

class Device extends Model implements Auditable
{
    const REPAIR_STATUS_FIXED = 1;
    const REPAIR_STATUS_REPAIRABLE = 2;
    const REPAIR_STATUS_ENDOFLIFE = 3;

    const SPARE_PARTS_NEEDED = 1;
    const SPARE_PARTS_NOT_NEEDED = 2;
    const SPARE_PARTS_UNKNOWN = 0;

    const PARTS_PROVIDER_MANUFACTURER = 1;
    const PARTS_PROVIDER_THIRD_PARTY = 2;

    use \OwenIt\Auditing\Auditable;
    protected $table = 'devices';
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

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'updated' => DeviceCreatedOrUpdated::class,
        'created' => DeviceCreatedOrUpdated::class,
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($device) {
            $device->barriers()->detach();
            $device->urls()->delete();
        });
    }

    // Setters

    //Getters

    public static function getDisplacementFactor()
    {
        return \App\Helpers\LcaStats::getDisplacementFactor();
    }

    public function ofThisUser($id)
    {
        //Tested
        return DB::select(DB::raw('SELECT * FROM `'.$this->table.'` WHERE `repaired_by` = :id'), ['id' => $id]);
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

                WHERE `event` = :event'), ['event' => $event]);
    }

    public function ofThisGroup($group)
    {
        //Tested
        return DB::select(DB::raw('SELECT * FROM `'.$this->table.'` AS `d`
                INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`
                INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event`
                WHERE `group` = :group'), ['group' => $group]);
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
        if ((! is_null($g) && is_numeric($g)) || (! is_null($year) && is_numeric($year))) {
            $sql .= ' INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event` ';
        }

        $sql .= ' WHERE `repair_status` > 0 ';

        if (! is_null($g) && is_numeric($g)) {
            $sql .= ' AND `group` = :g ';
        }
        if (! is_null($year) && is_numeric($year)) {
            $sql .= ' AND YEAR(`event_start_utc`) = :year ';
        }

        $sql .= ' GROUP BY `status`';

        if (! is_null($year) && is_numeric($year)) {
            $sql .= ', `event`';
        }

        if (! is_null($g) && is_numeric($g) && is_null($year)) {
            return DB::select(DB::raw($sql), ['g' => $g]);
        } elseif (! is_null($year) && is_numeric($year) && is_null($g)) {
            return DB::select(DB::raw($sql), ['year' => $year]);
        } elseif (! is_null($year) && is_numeric($year) && ! is_null($g) && is_numeric($g)) {
            return DB::select(DB::raw($sql), ['year' => $year, 'g' => $g]);
        }

        return DB::select(DB::raw($sql));
    }

    public function partyStatusCount($event)
    {
        $sql = 'SELECT COUNT(*) AS `counter`, `d`.`repair_status` AS `status`, `d`.`event`
                FROM `'.$this->table.'` AS `d`';

        $sql .= ' WHERE `repair_status` > 0 ';

        $sql .= ' GROUP BY `status`';

        if (! is_null($event) && is_numeric($event)) {
            $sql .= ' AND `event` = :event ';
        }

        return DB::select(DB::raw($sql), ['event' => $event]);
    }

    public function countByCluster($cluster, $group = null, $year = null)
    {
        $sql = 'SELECT COUNT(*) AS `counter`, `repair_status` FROM `'.$this->table.'` AS `d`
                INNER JOIN `events` AS `e`
                    ON `d`.`event` = `e`.`idevents`
                INNER JOIN `categories` AS `c`
                    ON `d`.`category` = `c`.`idcategories`
                WHERE `c`.`cluster` = :cluster AND `d`.`repair_status` > 0 ';

        if (! is_null($group)) {
            $sql .= ' AND `e`.`group` = :group ';
        }
        if (! is_null($year)) {
            $sql .= ' AND YEAR(`e`.`event_start_utc`) = :year ';
        }

        $sql .= ' GROUP BY `repair_status`
                ORDER BY `repair_status` ASC
                ';

        try {
            if (! is_null($group) && is_numeric($group) && is_null($year)) {
                return DB::select(DB::raw($sql), ['group' => $group, 'cluster' => $cluster]);
            } elseif (! is_null($year) && is_numeric($year) && is_null($group)) {
                return DB::select(DB::raw($sql), ['year' => $year, 'cluster' => $cluster]);
            } elseif (! is_null($year) && is_numeric($year) && ! is_null($group) && is_numeric($group)) {
                return DB::select(DB::raw($sql), ['year' => $year, 'group' => $group, 'cluster' => $cluster]);
            }

            return DB::select(DB::raw($sql), ['cluster' => $cluster]);
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
                WHERE 1=1 and `c`.`powered` = 1 AND `c`.`idcategories` <> '.env('MISC_CATEGORY_ID_POWERED');

        if (! is_null($status) && is_numeric($status)) {
            $sql .= ' AND `d`.`repair_status` = :status ';
        }
        if (! is_null($cluster) && is_numeric($cluster)) {
            $sql .= ' AND `c`.`cluster` = :cluster ';
        }
        if (! is_null($group) && is_numeric($group)) {
            $sql .= ' AND `e`.`group` = :group ';
        }

        $sql .= ' GROUP BY `d`.`category`
                 ORDER BY `counter` DESC';

        $sql .= (! is_null($cluster) ? '  LIMIT 1' : '');

        if (! is_null($cluster) && is_numeric($cluster)) {
            try {
                if (! is_null($group) && is_numeric($group) && is_null($status)) {
                    return DB::select(DB::raw($sql), ['group' => $group, 'cluster' => $cluster]);
                } elseif (! is_null($status) && is_numeric($status) && is_null($group)) {
                    return DB::select(DB::raw($sql), ['status' => $status, 'cluster' => $cluster]);
                } elseif (! is_null($status) && is_numeric($status) && ! is_null($group) && is_numeric($group)) {
                    return DB::select(DB::raw($sql), ['status' => $status, 'group' => $group, 'cluster' => $cluster]);
                }

                return DB::select(DB::raw($sql), ['cluster' => $cluster]);
            } catch (\Illuminate\Database\QueryException $e) {
                dd($e);
            }
        } else {
            try {
                if (! is_null($group) && is_numeric($group) && is_null($status)) {
                    return DB::select(DB::raw($sql), ['group' => $group]);
                } elseif (! is_null($status) && is_numeric($status) && is_null($group)) {
                    return DB::select(DB::raw($sql), ['status' => $status]);
                } elseif (! is_null($status) && is_numeric($status) && ! is_null($group) && is_numeric($group)) {
                    return DB::select(DB::raw($sql), ['status' => $status, 'group' => $group]);
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
        if (! is_null($cluster)) {
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

        if (! is_null($cluster)) {
            $sql .= ' AND cluster = :cluster ';
        }
        $sql .= 'GROUP BY devices.category
                    ORDER BY cluster ASC, success_rate '.$direction.' LIMIT 1';

        if (! is_null($cluster)) {
            return DB::select(DB::raw($sql), ['cluster' => $cluster]);
        }

        return DB::select(DB::raw($sql));
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
                    UNIX_TIMESTAMP(event_start_utc) AS `event_timestamp`,
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
        return $this->hasOne(\App\Category::class, 'idcategories', 'category');
    }

    public function deviceEvent()
    {
        return $this->hasOne(\App\Party::class, 'idevents', 'event');
    }

    public function urls()
    {
        return $this->hasMany(\App\DeviceUrl::class, 'device_id', 'iddevices');
    }

    public function barriers()
    {
        return $this->belongsToMany(\App\Barrier::class, 'devices_barriers', 'device_id', 'barrier_id');
    }

    /**
     * Powered estimate only takes precedence over category weight when Misc and if not 0.
     */
    public function eCo2Diverted($emissionRatio, $displacementFactor)
    {
        $footprint = 0;

        if ($this->isFixed()) {
            if ($this->deviceCategory->isMiscPowered() && $this->estimate > 0) {
                $footprint = $this->estimate * $emissionRatio;
            } else {
                $footprint = $this->deviceCategory->footprint;
            }
        }

        return $footprint * $displacementFactor;
    }

    /**
     * Unpowered estimate always takes precedence over category weight unless is is 0.
     *
     */
    public function uCo2Diverted($emissionRatio, $displacementFactor)
    {
        $footprint = 0;

        if ($this->isFixed()) {
            if ($this->estimate > 0) {
                $footprint = ($this->estimate * $emissionRatio);
            } else {
                $footprint = $this->deviceCategory->footprint;
            }
        }

        return $footprint * $displacementFactor;
    }

    /**
     * Powered estimate only takes precedence over category weight when Misc and if not 0.
     *
     */
    public function eWasteDiverted()
    {
        $ewasteDiverted = 0;

        if ($this->isFixed() && $this->deviceCategory->isPowered()) {
            if ($this->deviceCategory->isMiscPowered() && $this->estimate > 0) {
                $ewasteDiverted = $this->estimate;
            } else {
                $ewasteDiverted = $this->deviceCategory->weight;
            }
        }

        return $ewasteDiverted;
    }

    /**
     * Unpowered estimate always takes precedence over category weight unless it is 0.
     *
     */
    public function uWasteDiverted()
    {
        $wasteDiverted = 0;

        if ($this->isFixed() && $this->deviceCategory->isUnpowered()) {
            if ($this->estimate > 0) {
                $wasteDiverted = $this->estimate;
            } else {
                $wasteDiverted = $this->deviceCategory->weight;
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
        if (! empty($this->problem)) {
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
        if (! empty($this->age)) {
            return $this->age;
        }

        return '-';
    }

    public function getShortProblem($length = 60)
    {
        return Str::limit($this->problem, $length);
    }

    public function getImages()
    {
        $File = new \FixometerFile;

        return $File->findImages(env('TBL_DEVICES'), $this->iddevices);
    }

    public function fixedPoweredCount()
    {
        // We want fixed devices with an powered category.
        $count = self::where('repair_status', '=', env('DEVICE_FIXED'))->withCount(['deviceCategory' => function ($query) {
            $query->where('powered', 1);
        }])->get();

        $total = 0;
        foreach ($count as $c) {
            $total += $c->device_category_count;
        }

        return $total;
    }

    public function fixedUnpoweredCount()
    {
        // We want fixed devices with an unpowered category.
        $count = self::where('repair_status', '=', env('DEVICE_FIXED'))->withCount(['deviceCategory' => function ($query) {
            $query->where('powered', 0);
        }])->get();

        $total = 0;
        foreach ($count as $c) {
            $total += $c->device_category_count;
        }

        return $total;
    }

    public function unpoweredCount()
    {
        // We want devices with an unpowered category.
        $count = self::withCount(['deviceCategory' => function ($query) {
            $query->where('powered', 0);
        }])->get();

        $total = 0;
        foreach ($count as $c) {
            $total += $c->device_category_count;
        }

        return $total;
    }

    public function poweredCount()
    {
        // We want devices with an powered category.
        $count = self::withCount(['deviceCategory' => function ($query) {
            $query->where('powered', 1);
        }])->get();

        $total = 0;
        foreach ($count as $c) {
            $total += $c->device_category_count;
        }

        return $total;
    }

    public static function getItemTypes()
    {
        // List the item types
        $types = DB::table('devices')->whereNotNull('item_type')->select('item_type', DB::raw('COUNT(*) as count'))->groupBy('item_type')->orderBy('count', 'desc')->get()->toArray();

        return $types;
    }

    public function setProblemAttribute($value)
    {
        // Map null values to empty strings to avoid Metabase problems.
        $this->attributes['problem'] = $value ?? '';
    }
}

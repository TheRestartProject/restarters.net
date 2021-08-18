<?php

namespace App\Helpers;

use DB;

class LcaStats
{

    public static function getEmissionRatioUnpowered()
    {
        return env('EMISSION_RATIO_UNPOWERED');
    }

    public static function getEmissionRatioPowered()
    {
        return env('EMISSION_RATIO_POWERED');
    }

    public static function getDisplacementFactor()
    {
        return env('DISPLACEMENT_VALUE');
    }

    /**
     * Misc weight/footprint values must = 0 in categories table.
     * Unpowered estimate takes precedence over unpowered category weight.
     */
    public static function getWasteStats($group = null)
    {
        $dF = LcaStats::getDisplacementFactor();
        $eR = LcaStats::getEmissionRatioPowered();
        $uR = LcaStats::getEmissionRatioUnpowered();

        $t1 = "
SELECT
CASE WHEN (c.powered = 1) THEN (CASE WHEN (c.weight = 0) THEN (d.estimate + 0.00) ELSE c.weight END) ELSE 0 END  AS powered_device_weights,
CASE WHEN (c.powered = 0) THEN (CASE WHEN (COALESCE(d.estimate,0) + 0.00 = 0) THEN c.weight ELSE d.estimate END) ELSE 0 END AS unpowered_device_weights,
CASE WHEN (c.powered = 1) THEN (CASE WHEN (c.weight = 0) THEN ((d.estimate + 0.00) * $eR) ELSE c.footprint END) ELSE 0 END AS powered_footprints,
CASE WHEN (c.powered = 0) THEN (CASE WHEN (COALESCE(d.estimate,0) + 0.00 = 0) THEN c.footprint ELSE ((d.estimate + 0.00) * $uR) END) ELSE 0 END AS unpowered_footprints
FROM devices d, categories c, events e
WHERE d.category = c.idcategories
AND d.event = e.idevents
AND d.repair_status = 1
";
        if (!is_null($group) && is_numeric($group)) {
            $t1 .= " AND e.`group` = $group";
        }
        $sql = "
SELECT
SUM(t1.powered_device_weights) AS powered_waste,
SUM(t1.unpowered_device_weights) AS unpowered_waste,
SUM(t1.powered_footprints) * $dF AS powered_footprint,
SUM(t1.unpowered_footprints) * $dF AS unpowered_footprint
FROM ($t1) t1
";
        return DB::select(DB::raw($sql));
    }

    /**
     * DEPRECATED IN PRODUCTION
     *
     * TO BE MOVED SOMEWHERE ELSE
     *
     * CAN BE USED FOR CHECKING CURRENT STATUS OF STATIC RATIOS
     *
     * Calculates the average footprint per kilo of fixed devices.
     *
     * Calculated by looking at all of the fixed devices in the database,
     * and dividing the total footprint of their categories by the total weight of their
     * categories.
     *
     * Misc categories should not be included therefore
     * weight and footprint = must have 0 values.
     *
     * This ratio of CO2 to kilo of device is then used to estimate the footprint of
     * Misc devices that are recorded in the DB.
     */
    public static function calculatetEmissionRatioPowered()
    {
        $value = DB::select(DB::raw("
SELECT
SUM(c.footprint) / SUM(c.weight) AS ratio
FROM devices d
JOIN categories c ON c.idcategories = d.category
WHERE c.powered = 1
AND d.repair_status = 1
AND d.category <> 46
"));
        return $value[0]->ratio;
    }

    public static function calculatetEmissionRatioUnpowered()
    {
        $value = DB::select(DB::raw("
SELECT
SUM(c.footprint) / SUM(c.weight) AS ratio
FROM devices d
JOIN categories c ON c.idcategories = d.category
WHERE c.powered = 0
AND d.repair_status = 1
AND d.category <> 50
"));
        return $value[0]->ratio;
    }

    /**
     * BORKED ATTEMPT TO APPLY SENT FILTERS TO STATS QUERY
     * MAY BE REDUNDANT
     * SEE ApiController::getDevices()
     */
    public static function getWasteStatsFiltered($filters = [])
    {
        for($i=count($filters)-1;$i>=0;$i--) {
            if (strstr($filters[$i][0], 'repair_status')) {
                unset($filters[$i]);
            }
        }
        $filters[] = ['devices.repair_status', '=', 1];
        $dF = LcaStats::getDisplacementFactor();
        $eR = LcaStats::getEmissionRatioPowered();
        $uR = LcaStats::getEmissionRatioUnpowered();

        $t1 = DB::table('devices')->select(
            DB::raw("
                    CASE WHEN (categories.powered = 1) THEN (CASE WHEN (categories.weight = 0) THEN (devices.estimate + 0.00) ELSE categories.weight END) ELSE 0 END  AS powered_device_weights,
                    CASE WHEN (categories.powered = 0) THEN (CASE WHEN (COALESCE(devices.estimate,0) + 0.00 = 0) THEN categories.weight ELSE devices.estimate END) ELSE 0 END AS unpowered_device_weights,
                    CASE WHEN (categories.powered = 1) THEN (CASE WHEN (categories.weight = 0) THEN ((devices.estimate + 0.00) * $eR) ELSE categories.footprint END) ELSE 0 END AS powered_footprints,
                    CASE WHEN (categories.powered = 0) THEN (CASE WHEN (COALESCE(devices.estimate,0) + 0.00 = 0) THEN categories.footprint ELSE ((devices.estimate + 0.00) * $uR) END) ELSE 0 END AS unpowered_footprints
                    ")
        )
            ->join('categories', 'devices.category', '=', 'categories.idcategories')
            ->join('events', 'events.idevents', '=', 'devices.event')
            ->join('groups', 'events.group', '=', 'groups.idgroups')
            ->where($filters)
            ->get();

        $t2 =  DB::table($t1)->select(DB::raw("
                SUM(powered_device_weights) AS powered_waste,
                SUM(unpowered_device_weights) AS unpowered_waste,
                SUM(powered_footprints) * $dF AS powered_footprint,
                SUM(unpowered_footprints) * $dF AS unpowered_footprint
            "))->first();
        logger(print_r($t2, 1));
    }
}

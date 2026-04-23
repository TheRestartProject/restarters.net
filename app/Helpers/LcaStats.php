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
        $dF = self::getDisplacementFactor();
        $eR = self::getEmissionRatioPowered();
        $uR = self::getEmissionRatioUnpowered();

        $t1 = "
SELECT
CASE WHEN (c.powered = 1) THEN (CASE WHEN (c.weight = 0) THEN d.estimate ELSE c.weight END) ELSE 0 END  AS powered_device_weights,
CASE WHEN (c.powered = 0) THEN (CASE WHEN (d.estimate = 0) THEN c.weight ELSE d.estimate END) ELSE 0 END AS unpowered_device_weights,
CASE WHEN (c.powered = 1) THEN (CASE WHEN (c.weight = 0) THEN (d.estimate * $eR) ELSE c.footprint END) ELSE 0 END AS powered_footprints,
CASE WHEN (c.powered = 0) THEN (CASE WHEN (d.estimate = 0) THEN c.footprint ELSE (d.estimate * $uR) END) ELSE 0 END AS unpowered_footprints
FROM devices d, categories c, events e
WHERE d.category = c.idcategories
AND d.event = e.idevents
AND d.repair_status = 1
";
        if (! is_null($group) && is_numeric($group)) {
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

        return DB::select($sql);
    }
}

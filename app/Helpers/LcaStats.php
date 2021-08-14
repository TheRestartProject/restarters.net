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
     * Calculates the average footprint per kilo of fixed devices.
     *
     * Calculated by looking at all of the fixed devices in the database,
     * and dividing the total footprint of their categories by the total weight of their
     * categories.
     *
     * Misc categories should not be included therefore they must
     * weight and footprint = 0.
     *
     * This ratio of CO2 to kilo of device is then used to estimate the footprint of
     * Misc devices that are recorded in the DB.
     */
    public static function getEmissionRatioPoweredXX()
    {
        $value = DB::select(DB::raw("
SELECT
SUM(COALESCE(c.footprint, 0) / SUM(COALESCE(c.weight, 0)) AS ratio
FROM devices d
JOIN categories c ON c.idcategories = d.category
AND d.repair_status = 1
AND c.powered = 1
"));
        return $value[0]->ratio;
    }

    /**
     * Proposed replacement for Device->getWeights().
     * Adds calculation for unpowered categories with lca data.
     * Misc weight/footprint values = 0 in categories table.
     * Removal of hard-coded Misc ID.
     * Separate emission calculations for powered/unpowered devices.
     * PENDING confirmation of assumptions.
     * */
    public static function getWasteStats($group = null)
    {
        $sql = "
SELECT
CASE WHEN (c.weight = 0) THEN COALESCE(d.estimate,0) ELSE c.weight END AS device_weights,
CASE WHEN (c.powered = 1) THEN (CASE WHEN (c.weight = 0) THEN COALESCE(d.estimate,0) ELSE c.weight end) ELSE 0 END  AS powered_device_weights,
CASE WHEN (c.powered = 0) THEN (CASE WHEN (c.weight = 0) THEN COALESCE(d.estimate,0) ELSE c.weight end) ELSE 0 END AS unpowered_device_weights,
CASE WHEN (c.powered = 1) THEN (CASE WHEN (c.weight = 0) THEN (COALESCE(d.estimate,0) * @eRatio) ELSE c.footprint END) ELSE 0 END AS powered_footprints,
CASE WHEN (c.powered = 0) THEN (CASE WHEN (c.weight = 0) THEN (COALESCE(d.estimate,0) * @uRatio) ELSE c.footprint END) ELSE 0 END AS unpowered_footprints
FROM devices d, categories c, events e,
(SELECT @eRatio := :eRatio) er,
(SELECT @uRatio := :uRatio) ur
WHERE d.category = c.idcategories
AND d.event = e.idevents
AND d.repair_status = 1
";
        $params = [
            'displacement' => LcaStats::getDisplacementFactor(),
            'eRatio' => LcaStats::getEmissionRatioPowered(),
            'uRatio' => LcaStats::getEmissionRatioUnpowered()
        ];
        if (!is_null($group) && is_numeric($group)) {
            $sql .= ' AND e.`group` = :groupid';
            $params['groupid'] = $group;
        }

        $sql = "
SELECT
SUM(t1.device_weights) AS total_weight,
SUM(t1.powered_device_weights) AS powered_waste,
SUM(t1.unpowered_device_weights) AS unpowered_waste,
SUM(t1.powered_footprints) * @displacement AS powered_footprint,
SUM(t1.unpowered_footprints) * @displacement AS unpowered_footprint
FROM (
$sql
) t1,
(SELECT @displacement := :displacement) dp
";
        return DB::select(DB::raw($sql), $params);
    }
}

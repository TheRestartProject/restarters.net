<?php

namespace App\Helpers;

use DB;
class LcaStats
{

    public static function getEmissionRatioUnpowered()
    {
        return env('UNPOWERED_EMISSION_RATIO');
    }

    public static function getDisplacementFactor()
    {
        return env('DISPLACEMENT_VALUE');
    }

    /**
     * Calculates the average footprint per kilo of fixed devices.
     *
     * Calculated by looking at all of the fixed, non-misc devices in the database,
     * and dividing the total footprint of these devices by the total weight of these
     * devices.
     *
     * Categories table must hold 0 value for weight and footprint for Misc categories.
     *
     * This ratio of CO2 to kilo of device is then used to estimate the footprint of
     * miscellaneous devices that are recorded in the DB.
     */
    public static function getEmissionRatioPowered()
    {
        $result = DB::select(DB::raw("
SELECT
SUM(COALESCE(c.footprint, 0.0)) / SUM(COALESCE(c.weight, 0.0)) AS ratio
FROM devices d
JOIN categories c ON c.idcategories = d.category
AND d.repair_status = 1
AND c.powered = 1
"));
        return $result[0]->ratio;
    }

    /**
     * Proposed changes to getWeights().
     * Adds calculation for unpowered categories with lca data.
     * Misc weight/footprint values = 0 in categories table.
     * Removal of hard-coded Misc ids.
     * Separate emission calculations for powered/unpowered devices.
     * Assuming emission ratio calculated separately.
     * PENDING confirmation of assumptions.
     * SEE tests/Feature/FooStatsTest::get_weights_new().
     * */
    public static function getWasteStats($group = null)
    {
        $sql ="
SELECT
sum(case when (c.weight = 0) then (d.estimate + 0.0) else c.weight end) as total_weight,
sum(case when (c.powered = 1) then (case when (c.weight = 0) then (d.estimate + 0.0) else c.weight end) else 0 end) as powered_waste,
sum(case when (c.powered = 0) then (case when (c.weight = 0) then (d.estimate + 0.0) else c.weight end) else 0 end) as unpowered_waste,
sum(case when (c.powered = 1) then (case when (c.weight = 0) then (d.estimate + 0.0) * @eRatio else c.footprint * @displacement end) else 0 end) as powered_footprint,
sum(case when (c.powered = 0) then (case when (c.weight = 0) then (d.estimate + 0.0) * @uRatio else c.footprint * @displacement end) else 0 end) as unpowered_footprint
FROM devices d, categories c, events e,
(select @displacement := :displacement) dp,
(select @eRatio := :eRatio) er,
(select @uRatio := :uRatio) ur
WHERE d.category = c.idcategories
AND d.event = e.idevents
AND d.repair_status = 1
";

        $eRatio = LcaStats::getEmissionRatioPowered();
        $uRatio = LcaStats::getEmissionRatioUnpowered();
        $displacement = LcaStats::getDisplacementFactor();
        $params = ['displacement' => $displacement, 'eRatio' => $eRatio, 'uRatio' => $uRatio];

        if (!is_null($group) && is_numeric($group)) {
            $sql .= ' AND e.`group` = :groupid';
            $params['groupid'] = $group;

            return DB::select(DB::raw($sql), $params);
        }

        return DB::select(DB::raw($sql), $params);
    }
}

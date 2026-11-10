<?php

namespace App\Services;

use App\Helpers\LcaStats;

/**
 * The one rule for how much waste and CO2e a fixed item prevents (RES-2063), for powered and unpowered items
 * alike. The PHP and SQL forms here must agree; ItemImpactTest checks that they do.
 *
 * - An entered weight counts if it is between a hundredth of the category weight and 10 times it. Misc (a
 *   category without a weight) has no category weight to compare with: a Misc weight counts if it is up to
 *   20 times the typical Misc weight, or any weight while there is no typical weight yet.
 * - Waste is the entered weight if it counts, otherwise the category weight, or for Misc the typical Misc weight.
 * - CO2e is the entered weight times the category's CO2e per kg (footprint / weight) if it counts, otherwise
 *   the category footprint. Misc uses the general ratio for powered or unpowered items instead.
 *
 * CO2e here is before the displacement factor, which callers apply.
 */
class ItemImpact
{
    public const LOWER_FACTOR = 100;
    public const UPPER_FACTOR = 10;
    public const MISC_UPPER_FACTOR = 20;
    public const MISC_MIN = 0.001;

    // Stands in for "no upper limit" in SQL.
    private const NO_LIMIT = 1000000;

    public static function typicalMiscWeight(bool $powered): float
    {
        return app(TypicalMiscWeights::class)->get($powered);
    }

    /**
     * The weight used when nobody entered one, or the entered one does not count.
     */
    public static function typicalWeight(float $categoryWeight, bool $powered): float
    {
        $categoryWeight = self::kg($categoryWeight);

        return $categoryWeight > 0 ? $categoryWeight : self::typicalMiscWeight($powered);
    }

    /**
     * Category weights are stored as FLOAT, so 1.28 reads back as 1.2799999713897705. They are used to the gram,
     * as SQL does with ROUND(c.weight, 3), so PHP and SQL agree at the edges of the band.
     */
    private static function kg(float $weight): float
    {
        return round($weight, 3);
    }

    /**
     * The lowest and highest entered weights that count. The highest is null when there is no limit.
     *
     * @return array{0: float, 1: ?float}
     */
    public static function countedRange(float $categoryWeight, bool $powered): array
    {
        $categoryWeight = self::kg($categoryWeight);

        // The same floating-point arithmetic as the SQL in sqlCounts(), so the two agree at the edges.
        if ($categoryWeight > 0) {
            return [$categoryWeight / self::LOWER_FACTOR, $categoryWeight * self::UPPER_FACTOR];
        }

        $typical = self::typicalMiscWeight($powered);

        return [self::MISC_MIN, $typical > 0 ? $typical * self::MISC_UPPER_FACTOR : null];
    }

    public static function counts(?float $entered, float $categoryWeight, bool $powered): bool
    {
        if ($entered === null || $entered <= 0) {
            return false;
        }

        [$min, $max] = self::countedRange($categoryWeight, $powered);

        return $entered >= $min && ($max === null || $entered <= $max);
    }

    public static function generalRatio(bool $powered): float
    {
        return (float) ($powered ? LcaStats::getEmissionRatioPowered() : LcaStats::getEmissionRatioUnpowered());
    }

    public static function waste(?float $entered, float $categoryWeight, bool $powered): float
    {
        return self::counts($entered, $categoryWeight, $powered)
            ? $entered
            : self::typicalWeight($categoryWeight, $powered);
    }

    /**
     * CO2e before the displacement factor.
     */
    public static function co2(?float $entered, float $categoryWeight, float $footprint, bool $powered, ?float $generalRatio = null): float
    {
        $generalRatio ??= self::generalRatio($powered);
        $categoryWeight = self::kg($categoryWeight);
        $ratio = $categoryWeight > 0 ? $footprint / $categoryWeight : $generalRatio;

        if (self::counts($entered, $categoryWeight, $powered)) {
            return $entered * $ratio;
        }

        return $categoryWeight > 0 ? $footprint : self::typicalMiscWeight($powered) * $generalRatio;
    }

    /**
     * SQL for an item's waste, given the devices and categories table aliases.
     */
    public static function sqlWaste(string $d = 'd', string $c = 'c'): string
    {
        $counts = self::sqlCounts($d, $c);
        $typical = self::sqlTypicalWeight($c);

        return "(CASE WHEN $counts THEN $d.estimate ELSE $typical END)";
    }

    /**
     * SQL for an item's CO2e before the displacement factor.
     */
    public static function sqlCo2(string $d = 'd', string $c = 'c'): string
    {
        $counts = self::sqlCounts($d, $c);
        $general = self::sqlGeneralRatio($c);
        $typicalMisc = self::sqlTypicalMiscWeight($c);

        $weight = "ROUND($c.weight, 3)";

        return "(CASE WHEN $counts THEN $d.estimate * (CASE WHEN $weight > 0 THEN $c.footprint / $weight ELSE $general END)"
            . " ELSE (CASE WHEN $weight > 0 THEN $c.footprint ELSE $typicalMisc * $general END) END)";
    }

    private static function sqlCounts(string $d, string $c): string
    {
        $lower = self::LOWER_FACTOR;
        $upper = self::UPPER_FACTOR;
        $cap = fn (bool $powered) => self::number(self::countedRange(0, $powered)[1] ?? self::NO_LIMIT);

        $weight = "ROUND($c.weight, 3)";

        return "($d.estimate > 0 AND (CASE WHEN $weight > 0"
            . " THEN $d.estimate BETWEEN $weight / $lower AND $weight * $upper"
            . " ELSE $d.estimate <= (CASE WHEN $c.powered = 1 THEN {$cap(true)} ELSE {$cap(false)} END) END))";
    }

    private static function sqlTypicalWeight(string $c): string
    {
        return "(CASE WHEN ROUND($c.weight, 3) > 0 THEN ROUND($c.weight, 3) ELSE " . self::sqlTypicalMiscWeight($c) . ' END)';
    }

    private static function sqlTypicalMiscWeight(string $c): string
    {
        return "(CASE WHEN $c.powered = 1 THEN " . self::number(self::typicalMiscWeight(true))
            . ' ELSE ' . self::number(self::typicalMiscWeight(false)) . ' END)';
    }

    private static function sqlGeneralRatio(string $c): string
    {
        return "(CASE WHEN $c.powered = 1 THEN " . self::number(self::generalRatio(true))
            . ' ELSE ' . self::number(self::generalRatio(false)) . ' END)';
    }

    private static function number(float $value): string
    {
        return sprintf('%.8F', $value);
    }
}

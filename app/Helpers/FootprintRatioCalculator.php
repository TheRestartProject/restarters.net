<?php

namespace App\Helpers;

class FootprintRatioCalculator
{
    public static function calculateRatio()
    {
        return env('EMISSION_RATIO_POWERED');
    }
}

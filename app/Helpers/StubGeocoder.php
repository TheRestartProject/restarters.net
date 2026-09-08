<?php

namespace App\Helpers;

/**
 * A Geocoder that answers from a fixed table instead of calling Google.
 *
 * CircleCI does not pass project environment variables to jobs built from
 * forked pull requests, so GOOGLE_API_CONSOLE_KEY is empty on those runs.
 * Every geocode then returns false, group creation 422s on
 * groups.geocode_failed, and the whole Playwright suite fails at its first
 * createGroup() - which looks exactly like the contributor having broken
 * something. This stub keeps those runs meaningful, and keeps the suite off
 * the live Google API generally.
 *
 * Only used when GEOCODER_STUB is set, which CI does when there is no key.
 */
class StubGeocoder extends Geocoder
{
    /**
     * Coordinates the end-to-end tests need. Anything else gets London, so a
     * new test that geocodes somewhere unlisted still passes rather than
     * failing for a reason that has nothing to do with what it is testing.
     */
    private const PLACES = [
        'london' => [51.5072178, -0.1275862, 'GB'],
        'london, uk' => [51.5072178, -0.1275862, 'GB'],
        'edinburgh' => [55.9533456, -3.1883749, 'GB'],
        'brussels' => [50.8476424, 4.3571696, 'BE'],
        'paris' => [48.8575475, 2.3513765, 'FR'],
    ];

    public function geocode($location)
    {
        // Preserve the sentinel the tests use to exercise the failure path.
        if ($location === 'ForceGeocodeFailure') {
            return false;
        }

        [$latitude, $longitude, $country_code] =
            self::PLACES[mb_strtolower(trim((string) $location))] ?? self::PLACES['london'];

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country_code' => $country_code,
        ];
    }
}

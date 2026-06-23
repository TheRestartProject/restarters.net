<?php

namespace Tests\Support;

use App\Helpers\Geocoder;

/**
 * Deterministic Geocoder for the test suite.
 *
 * The real Geocoder makes a live HTTP call to the Google Maps Geocoding API. That
 * API is rate-limited and intermittently errors, so any test that geocodes a
 * location (event/group creation, "nearby" calculations) was at the mercy of the
 * network — most visibly BasicTest::testUpcomingEvents, which flaked with
 * "Undefined array key 'nearby'" whenever a live geocode failed and the event lost
 * its coordinates.
 *
 * This fake is bound globally in Tests\TestCase::setUp() so no test ever hits the
 * live API. Tests that specifically exercise the geocode-failure path bind their
 * own failing mock in their setUp(), which overrides this binding.
 */
class GeocoderMock extends Geocoder
{
    public function __construct()
    {
    }

    public function geocode($location)
    {
        // Preserve the real Geocoder's sentinel so failure-path tests still work.
        if ($location === 'ForceGeocodeFailure') {
            return null;
        }

        // Central London. The bulk of the test fixtures (createGroup() and
        // PartyFactory both default to London) resolve here, and several
        // distance-based "nearby" assertions compare against hard-coded London
        // coordinates, so pinning every fixture to a single real London point
        // keeps that logic deterministic and matches what the live API returned
        // for these locations.
        return [
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
            'country_code' => 'GB',
        ];
    }
}

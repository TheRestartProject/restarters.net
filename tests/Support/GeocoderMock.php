<?php

namespace Tests\Support;

use App\Helpers\Geocoder;

/**
 * Deterministic, location-aware Geocoder for the test suite.
 *
 * The real Geocoder makes a live HTTP call to the Google Maps Geocoding API, which is
 * rate-limited and non-deterministic. Tests that geocode a location were at the mercy
 * of Google's responses — e.g. APIv2GroupTest::testLocales flipped between Belgium and
 * the United Kingdom, and the "bad location" tests only passed while Google happened to
 * reject the gibberish string.
 *
 * This fake is bound globally in Tests\TestCase::setUp() so no test hits the live API.
 * It recognises the specific places the suite uses and returns fixed results for them;
 * anything it does not recognise (gibberish like "zzzz", "ZZZZ", or the
 * ForceGeocodeFailure sentinel) returns null — a geocode failure — which is exactly
 * what the bad-location / invalid-location tests assert.
 *
 * Tests that need a different result bind their own mock in setUp(), overriding this.
 */
class GeocoderMock extends Geocoder
{
    public function __construct()
    {
    }

    public function geocode($location)
    {
        // Match on CITY names only, never on an appended country. The profile editor
        // geocodes "<townCity>, <country>" (e.g. "ZZZZ, United Kingdom"), so matching a
        // country name would wrongly turn gibberish towns into a valid location.
        $loc = strtolower((string) $location);

        // Belgian cities used in tests ("Brussels, Belgium", "Ghent, Belgium").
        if (str_contains($loc, 'brussels') || str_contains($loc, 'ghent')) {
            return ['latitude' => 50.8503, 'longitude' => 4.3517, 'country_code' => 'BE'];
        }

        // Lancaster, UK (group / network creation tests) — kept distinct from London so
        // any distance-based logic stays correct.
        if (str_contains($loc, 'lancaster')) {
            return ['latitude' => 54.0466, 'longitude' => -2.7988, 'country_code' => 'GB'];
        }

        // London — the bulk of the fixtures (createGroup() default, the PartyFactory
        // default address "… London SW9 7QD", "London", "London, UK", "London, United Kingdom").
        if (str_contains($loc, 'london')) {
            return ['latitude' => 51.5073509, 'longitude' => -0.1277583, 'country_code' => 'GB'];
        }

        // Anything else — gibberish ("zzzz", "ZZZZ, United Kingdom"), the ForceGeocodeFailure
        // sentinel, an unknown place — is a geocode failure (null), matching the real API for
        // un-geocodable input.
        return null;
    }
}

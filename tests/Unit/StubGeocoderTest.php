<?php

use App\Helpers\Geocoder;
use App\Helpers\StubGeocoder;
use Tests\TestCase;

class StubGeocoderTest extends TestCase
{
    /**
     * The property that matters in production: nothing stubs geocoding unless
     * GEOCODER_STUB is deliberately set, which only CI does and only when it
     * has no key. (The container binding itself can't be asserted here -
     * TestCase binds a GeocoderMock for every test.)
     */
    public function testStubIsOffUnlessDeliberatelyEnabled(): void
    {
        self::assertFalse((bool) config('restarters.geocoder_stub'));
    }

    public function testStubStillSatisfiesTheGeocoderContract(): void
    {
        self::assertInstanceOf(Geocoder::class, new StubGeocoder());
    }

    /**
     * The end-to-end suite always types "London, UK", so that is the one
     * answer the stub has to get right for CI to be worth anything.
     */
    public function testAnswersTheAddressTheEndToEndTestsUse(): void
    {
        $geocoded = (new StubGeocoder())->geocode('London, UK');

        self::assertEqualsWithDelta(51.5072178, $geocoded['latitude'], 0.001);
        self::assertEqualsWithDelta(-0.1275862, $geocoded['longitude'], 0.001);
        self::assertEquals('GB', $geocoded['country_code']);
    }

    public function testIsNotCaseOrWhitespaceSensitive(): void
    {
        self::assertEquals(
            (new StubGeocoder())->geocode('London, UK'),
            (new StubGeocoder())->geocode('  LONDON, uk ')
        );
    }

    /**
     * An unlisted place still geocodes, so a new test that happens to use one
     * fails on what it is actually testing rather than on the stub.
     */
    public function testFallsBackRatherThanFailingForAnUnlistedPlace(): void
    {
        $geocoded = (new StubGeocoder())->geocode('Somewhere nobody listed');

        self::assertNotFalse($geocoded);
        self::assertArrayHasKey('latitude', $geocoded);
    }

    /**
     * The suite uses this sentinel to exercise the geocode-failure path, so
     * the stub has to keep failing for it.
     */
    public function testStillFailsForTheForcedFailureSentinel(): void
    {
        self::assertFalse((new StubGeocoder())->geocode('ForceGeocodeFailure'));
    }
}

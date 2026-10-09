<?php

use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Tests\TestCase;

class GeocoderTest extends TestCase
{
    /**
     * Geocoder::geocode() resolves the provider chain out of the container, so a
     * test binds its own in place of the real Mapbox provider. Nothing here
     * touches the network: these tests are about how we read a provider's answer,
     * not whether Mapbox is up.
     */
    private function bindGeocoderReturning(array $addresses): void
    {
        $collection = new AddressCollection($addresses);

        // instance(), not bind(): geocoder-laravel registers a singleton, and by
        // the time a test runs it may already be resolved - in which case bind()
        // is ignored and the real Mapbox provider answers. That failure looks like a
        // wrong country code rather than a network call, so it is worth being
        // explicit about.
        $fake = new class($collection)
            {
                private $collection;

                public function __construct($collection)
                {
                    $this->collection = $collection;
                }

                public function geocodeQuery($query)
                {
                    return new class($this->collection)
                    {
                        private $collection;

                        public function __construct($collection)
                        {
                            $this->collection = $collection;
                        }

                        public function get()
                        {
                            return $this->collection;
                        }
                    };
                }
            };

        // Both keys: geocoder-laravel binds the string 'geocoder' through to
        // ProviderAndDumperAggregator, which is itself a singleton, so replacing
        // only the string leaves the real provider reachable - and a test that
        // "passes" then is really talking to Mapbox.
        $this->app->instance('geocoder', $fake);
        $this->app->instance(\Geocoder\Laravel\ProviderAndDumperAggregator::class, $fake);
    }

    private function bindGeocoderThrowing(\Throwable $e): void
    {
        $fake = new class($e)
            {
                private $e;

                public function __construct($e)
                {
                    $this->e = $e;
                }

                public function geocodeQuery($query)
                {
                    throw $this->e;
                }
            };

        $this->app->instance('geocoder', $fake);
        $this->app->instance(\Geocoder\Laravel\ProviderAndDumperAggregator::class, $fake);
    }

    private function address(array $over = []): Address
    {
        return Address::createFromArray(array_merge([
            'providedBy' => 'test',
            'latitude' => 51.4643585,
            'longitude' => -0.1135401,
            'country' => 'United Kingdom',
            'countryCode' => 'GB',
        ], $over));
    }

    public function testGeocode(): void
    {
        $this->bindGeocoderReturning([$this->address()]);

        $ret = (new \App\Helpers\Geocoder)->geocode('6 Canterbury Crescent, London SW9 7QD');

        $this->assertEquals(round(51.4643585, 2), round($ret['latitude'], 2));
        $this->assertEquals(round(-0.1135401, 2), round($ret['longitude'], 2));
        $this->assertEquals('GB', $ret['country_code']);
    }

    public function testGeocodeUsesTheProvidersCountryCodeRatherThanItsName(): void
    {
        // The whole point of reading getCountry()->getCode(): no matching a
        // display name against our own country list.
        $this->bindGeocoderReturning([$this->address([
            'country' => 'Belgique',
            'countryCode' => 'BE',
        ])]);

        $this->assertEquals('BE', (new \App\Helpers\Geocoder)->geocode('Brussels')['country_code']);
    }

    public function testGeocodeReturnsFalseWhenNothingFound(): void
    {
        $this->bindGeocoderReturning([]);

        $this->assertFalse((new \App\Helpers\Geocoder)->geocode('nowhere at all'));
    }

    public function testGeocodeCopesWithAnAddressCarryingNoCountry(): void
    {
        $this->bindGeocoderReturning([$this->address(['country' => null, 'countryCode' => null])]);

        $ret = (new \App\Helpers\Geocoder)->geocode('somewhere');

        $this->assertNull($ret['country_code']);
        $this->assertEquals(round(51.4643585, 2), round($ret['latitude'], 2));
    }

    public function testGeocodeReturnsFalseWhenTheProviderFails(): void
    {
        // With no fallback provider there is no chain to swallow a bad token or
        // an unreachable Mapbox: the helper has to, so the caller gets a failed
        // geocode (a validation message) rather than a 500.
        $this->bindGeocoderThrowing(new \Geocoder\Exception\InvalidCredentials('Invalid token'));

        $this->assertFalse((new \App\Helpers\Geocoder)->geocode('6 Canterbury Crescent'));
    }

    public function testForcedFailureShortCircuits(): void
    {
        $this->bindGeocoderReturning([$this->address()]);

        $this->assertFalse((new \App\Helpers\Geocoder)->geocode('ForceGeocodeFailure'));
    }
}

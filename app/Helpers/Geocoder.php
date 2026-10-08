<?php

namespace App\Helpers;

use Geocoder\Provider\Mapbox\Mapbox;
use Geocoder\Query\GeocodeQuery;
use Illuminate\Support\Facades\Log;

class Geocoder
{
    public function __construct()
    {
    }

    public function geocode($location)
    {
        if ($location != 'ForceGeocodeFailure') {
            try {
                // Mapbox, configured in config/geocoder.php and resolved via the container so tests can bind a
                // fake in its place.  There is no fallback provider.  This is the same geocoder that
                // App\Console\Commands\SetPlaceNetworkData uses for reverse geocoding.
                $geocodeResponse = app('geocoder')->geocodeQuery(
                    GeocodeQuery::create($location)->withData('location_type', [Mapbox::TYPE_PLACE, Mapbox::TYPE_ADDRESS])
                );
                $addressCollection = $geocodeResponse->get();
                // get(0) throws OutOfBounds on an empty collection, so an address the
                // provider can't place would crash rather than returning false.
                $address = $addressCollection->isEmpty() ? null : $addressCollection->first();

                if ($address && $address->getCoordinates()) {
                    // The provider gives us both the country name and its ISO code - use the code directly rather
                    // than trying to match the name against our own country list.
                    $country = $address->getCountry();

                    return [
                        'latitude' => $address->getCoordinates()->getLatitude(),
                        'longitude' => $address->getCoordinates()->getLongitude(),
                        'country_code' => $country ? $country->getCode() : null,
                    ];
                }
            } catch (\Throwable $e) {
                // A bad token, an exhausted quota or Mapbox being unreachable is a failed geocode as far as the
                // caller is concerned: it becomes a validation message, not a 500.  Log it so a misconfiguration
                // is still visible.
                Log::error("Geocoding '{$location}' failed: " . $e->getMessage());
            }
        }

        return false;
    }
}

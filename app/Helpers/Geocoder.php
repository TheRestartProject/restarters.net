<?php

namespace App\Helpers;

use Geocoder\Provider\Mapbox\Mapbox;
use Geocoder\Query\GeocodeQuery;

class Geocoder
{
    public function __construct()
    {
    }

    public function geocode($location)
    {
        if ($location != 'ForceGeocodeFailure') {
            // Use the geocoder configured in config/geocoder.php - a chain of Mapbox (primary) and GeoPlugin
            // (fallback), resolved via the container so tests can bind a mock Geocoder instead. This is the
            // same pattern used by App\Console\Commands\SetPlaceNetworkData for reverse geocoding.
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
        }

        return false;
    }
}

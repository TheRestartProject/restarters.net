<?php

namespace Tests\Unit\t;

use Tests\TestCase;

class GeocoderTest extends TestCase
{
    public function testGeocode() {
        $geocoder = new \App\Helpers\Geocoder();
        $ret = $geocoder->geocode('6 Canterbury Crescent, London SW9 7QD');
        $this->assertEquals(51.46398, $ret['latitude']);
        $this->assertEquals(-0.11302, $ret['longitude']);
        $this->assertEquals('United Kingdom', $ret['country']);
    }
}

<?php


use Tests\TestCase;

class GeocoderTest extends TestCase
{
    public function testGeocode(): void {
        $geocoder = new \App\Helpers\Geocoder();
        $ret = $geocoder->geocode('6 Canterbury Crescent, London SW9 7QD');
        $this->assertEquals(round(51.4643585, 2), round($ret['latitude'], 2));
        $this->assertEquals(round(-0.1135401, 2), round($ret['longitude'], 2));
        $this->assertEquals('GB', $ret['country_code']);
    }
}

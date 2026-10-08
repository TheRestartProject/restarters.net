<?php

use Tests\TestCase;

class GeocoderTest extends TestCase
{
    /**
     * A trimmed response for the address below, keeping the parts geocode()
     * reads: the location and the country component.
     */
    private function cannedResponse(): string
    {
        return json_encode([
            'status' => 'OK',
            'results' => [
                [
                    'geometry' => [
                        'location' => ['lat' => 51.4643585, 'lng' => -0.1135401],
                    ],
                    'address_components' => [
                        ['short_name' => 'SW9 7QD', 'types' => ['postal_code']],
                        ['short_name' => 'GB', 'types' => ['country', 'political']],
                    ],
                ],
            ],
        ]);
    }

    private function geocoderReturning($body): \App\Helpers\Geocoder
    {
        return new class($body) extends \App\Helpers\Geocoder {
            private $body;

            public function __construct($body)
            {
                parent::__construct();
                $this->body = $body;
            }

            protected function fetch($url)
            {
                return $this->body;
            }
        };
    }

    public function testGeocode(): void
    {
        $ret = $this->geocoderReturning($this->cannedResponse())
            ->geocode('6 Canterbury Crescent, London SW9 7QD');

        $this->assertEquals(round(51.4643585, 2), round($ret['latitude'], 2));
        $this->assertEquals(round(-0.1135401, 2), round($ret['longitude'], 2));
        $this->assertEquals('GB', $ret['country_code']);
    }

    public function testGeocodeReturnsFalseWhenNothingFound(): void
    {
        $empty = json_encode(['status' => 'ZERO_RESULTS', 'results' => []]);

        $this->assertFalse($this->geocoderReturning($empty)->geocode('nowhere at all'));
    }

    public function testGeocodeReturnsFalseWhenTheRequestFails(): void
    {
        // file_get_contents returns false on a failed request.  This is the case
        // that made the old test error with "array offset on value of type bool"
        // whenever Google was unreachable or the key was unhappy.
        $this->assertFalse($this->geocoderReturning(false)->geocode('6 Canterbury Crescent'));
    }

    public function testForcedFailureShortCircuits(): void
    {
        $this->assertFalse($this->geocoderReturning($this->cannedResponse())->geocode('ForceGeocodeFailure'));
    }
}

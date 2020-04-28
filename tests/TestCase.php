<?php

namespace Tests;

use App\Network;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp()
    {
        parent::setUp();

        Network::truncate();

        $network = new Network();
        $network->name = "Restarters";
        $network->shortname = "restarters";
        $network->save();
    }
}

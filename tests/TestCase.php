<?php

namespace Tests;

use App\Network;

use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use DB;
use Hash;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private $userCount = 0;

    public function setUp()
    {
        parent::setUp();

        DB::statement("SET foreign_key_checks=0");
        Network::truncate();
        User::truncate();
        DB::statement("SET foreign_key_checks=1");

        $network = new Network();
        $network->name = "Restarters";
        $network->shortname = "restarters";
        $network->save();

        $this->withoutExceptionHandling();
        app('honeypot')->disable();
    }

    public function userAttributes() {
        // Return a test user.
        $userAttributes = [];
        $userAttributes['name'] = "Test" . $this->userCount++;
        $userAttributes['email'] = $userAttributes['name'].'@restarters.dev';
        $userAttributes['age'] = '1982';
        $userAttributes['country'] = 'GBR';
        $userAttributes['password'] = 'letmein';
        $userAttributes['password_confirmation'] = 'letmein';
        $userAttributes['my_time'] = Carbon::now();
        $userAttributes['consent_gdpr'] = true;
        $userAttributes['consent_future_data'] = true;

        return $userAttributes;
    }

    public function loginAsTestUser() {
        $response = $this->post('/user/register/',  $this->userAttributes());
        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
    }
}

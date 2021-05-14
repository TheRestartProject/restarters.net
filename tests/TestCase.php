<?php

namespace Tests;

use App\Network;

use App\Role;
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

        // Create the jane@bloggs.net user which is commonly used in dev environments.  This means that
        // we don't have to manually recreate it after we run a test in dev.
        $this->createJane();
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

    public function createJane() {
        $user = factory(User::class)->create();
        $user->name = 'Jane Bloggs';
        $user->email = 'jane@bloggs.net';
        $user->password = Hash::make('passw0rd');
        $user->role = Role::ADMINISTRATOR;
        $user->consent_gdpr = true;
        $user->consent_future_data = true;
        $user->save();
    }
}

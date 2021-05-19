<?php

namespace Tests;

use App\Group;
use App\Network;

use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use DB;
use Hash;
use Auth;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private $userCount = 0;
    private $groupCount = 0;

    public function setUp()
    {
        parent::setUp();

        DB::statement("SET foreign_key_checks=0");
        Network::truncate();
        Group::truncate();
        User::truncate();
        DB::statement("TRUNCATE group_network");
        DB::statement("SET foreign_key_checks=1");

        $network = new Network();
        $network->name = "Restarters";
        $network->shortname = "restarters";
        $network->save();

        $this->withoutExceptionHandling();
        app('honeypot')->disable();

        // We don't yet have a Discourse test environment.
        config(['restarters.features.discourse_integration' => false]);

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

    public function loginAsTestUser($role = Role::RESTARTER) {
        // This is testing the external interface, whereas actingAs() wouldn't be.
        $response = $this->post('/user/register/',  $this->userAttributes($role));
        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        // Set the role.
        Auth::user()->role = $role;
    }

    public function createGroup($name = 'Test Group', $website = 'https://therestartproject.org', $location = 'London', $text = 'Some text.') {
        $response = $this->post('/group/create',  [
            'name' => $name . $this->groupCount++,
            'website' => $website,
            'location' => $location,
            'free_text' => $text
        ]);

        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/group/edit'));
        $p = strrpos($redirectTo, '/');
        $idgroups = substr($redirectTo, $p + 1);

        return $idgroups;
    }

    public function createJane() {
        $user = factory(User::class)->create([
            'name' => 'Jane Bloggs',
            'email' => 'jane@bloggs.net',
            'password' => Hash::make('passw0rd'),
            'role' => Role::ADMINISTRATOR,
            'consent_gdpr' => true,
            'consent_future_data' => true,
            'repairdir_role' => Role::REPAIR_DIRECTORY_SUPERADMIN
        ]);
        
        $user->save();
    }
}

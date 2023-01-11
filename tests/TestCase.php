<?php

namespace Tests;

use App\Audits;
use App\Brands;
use App\Category;
use App\Device;
use App\DeviceBarrier;
use App\EventsUsers;
use App\Group;
use App\GroupNetwork;
use App\GroupTags;
use App\Images;
use App\Network;
use App\Party;
use App\Role;
use App\Skills;
use App\UsersSkills;
use App\User;
use App\UserGroups;
use App\Xref;
use Auth;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\DomCrawler\Crawler;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private $userCount = 0;
    private $groupCount = 0;
    private $DOM = null;
    public $lastResponse = null;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET foreign_key_checks=0');
        Network::truncate();
        Group::truncate();
        User::truncate();
        Audits::truncate();
        EventsUsers::truncate();
        UserGroups::truncate();
        DeviceBarrier::truncate();
        Device::truncate();
        Party::truncate();
        GroupNetwork::truncate();
        Category::truncate();
        Brands::truncate();
        GroupTags::truncate();
        Xref::truncate();
        Images::truncate();
        UsersSkills::truncate();
        Skills::truncate();
        DB::statement('delete from audits');
        DB::delete('delete from user_network');
        DB::delete('delete from grouptags_groups');
        DB::table('notifications')->truncate();
        DB::statement('SET foreign_key_checks=1');
        DB::delete('delete from devices_faults_vacuums_ora_opinions');

        // Set up random auto increment values.  This avoids tests working because everything is 1.
        //
        // Some tables (e.g. network) have a tinyint as the ID, so we must be careful not to create values that
        // overflow this.  Also avoid the magic 29 value, which is a "superhero" user (see ExportController).
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table)
        {
            foreach ($table as $field => $tablename) {
                try {
                    do {
                        $val = rand(1, 100);
                    } while ($val == 29);

                    // This will throw an exception if the table doesn't have auto increment.
                    DB::update("ALTER TABLE $tablename AUTO_INCREMENT = " . $val . ";");
                } catch (\Exception $e) {
                }
            }
        }

        $network = new Network();
        $network->name = 'Restarters';
        $network->shortname = 'restarters';
        $network->save();

        $this->withoutExceptionHandling();
        app('honeypot')->disable();

        Category::factory()->count(1)->cat1()->create();
        Category::factory()->count(1)->cat2()->create();
        Category::factory()->count(1)->cat3()->create();
        Category::factory()->count(1)->mobile()->create();
        Category::factory()->count(1)->misc()->create();
        Category::factory()->count(1)->desktopComputer()->create();

        // We manipulate some globals for image upload testing.
        \FixometerFile::$uploadTesting = FALSE;

        if (isset($_FILES)) {
            unset($_FILES);
        }

        $this->processQueuedNotifications();
    }

    public function userAttributes()
    {
        // Return a test user.
        $userAttributes = [];
        $userAttributes['name'] = 'Test'.uniqid($this->userCount++, true);
        $userAttributes['email'] = $userAttributes['name'].'@restarters.dev';
        $userAttributes['age'] = '1982';
        $userAttributes['country'] = 'GBR';
        $userAttributes['password'] = 'letmein';
        $userAttributes['password_confirmation'] = 'letmein';
        $userAttributes['my_time'] = Carbon::now();
        $userAttributes['consent_gdpr'] = true;
        $userAttributes['consent_future_data'] = true;
        $userAttributes['city'] = 'London';
        $userAttributes['wiki_sync_status'] = 0;

        return $userAttributes;
    }

    public function loginAsTestUser($role = Role::RESTARTER)
    {
        // This is testing the external interface, whereas actingAs() wouldn't be.
        $response = $this->get('/logout');
        $response = $this->post('/user/register/', $this->userAttributes($role));

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        // Set the role.
        Auth::user()->role = $role;

        // Ensure API token in case we need to make API calls.
        Auth::user()->ensureAPIToken();

        return Auth::user();
    }

    public function createGroup($name = 'Test Group', $website = 'https://therestartproject.org', $location = 'London', $text = 'Some text.', $assert = true, $approve = true)
    {
        $idgroups = null;

        // Get the dashboard.  This will ensure that we have set the repair_network on the user.
        $this->get('/');

        // We create groups using the API.
        $user = Auth::user();

        $this->lastResponse = $this->post('/api/v2/groups?api_token=' . $user->api_token, [
             'name' => $name.$this->groupCount++,
             'website' => $website,
             'location' => $location,
             'description' => $text,
             'timezone' => 'Europe/London'
        ]);

        if ($assert) {
            $this->assertTrue($this->lastResponse->isSuccessful());
            $json = json_decode($this->lastResponse->getContent(), true);
            $this->assertTrue(array_key_exists('id', $json));
            $idgroups = $json['id'];

            if ($approve) {
                $group = Group::find($idgroups);
                $group->wordpress_post_id = '99999';
                $group->save();
            }

            // Currently logged in user should be present, with status 1 = approved.
            $member = UserGroups::where('group', $idgroups)->first();
            $this->assertEquals(1, $member->status);
            $this->assertEquals(3, $member->role);
            $this->assertEquals(Auth::user()->id, $member->user);
        }

        return $idgroups;
    }

    public function createDevice($idevents, $type)
    {
        $deviceAttributes = factory(Device::class)->states($type)->raw();

        $deviceAttributes['event_id'] = $idevents;
        $deviceAttributes['quantity'] = 1;

        $response = $this->post('/device/create', $deviceAttributes);
        $iddevices = Device::latest()->first()->iddevices;
        $this->assertNotNull($iddevices);

        return $iddevices;
    }

    public function createEvent($idgroups, $date)
    {
        // Create a party for the specific group.
        $eventAttributes = Party::factory()->raw();
        $eventAttributes['group'] = $idgroups;

        $event_start = Carbon::createFromTimestamp(strtotime($date))->setTimezone('UTC');
        $event_end = Carbon::createFromTimestamp(strtotime($date))->setTimezone('UTC')->addHour(2);

        $eventAttributes['event_start_utc'] = $event_start->toIso8601String();
        $eventAttributes['event_end_utc'] = $event_end->toIso8601String();

        $response = $this->post('/party/create/', $eventAttributes);

        // Need to reformat start/end for row comparison.
        $eventAttributes['event_start_utc'] = $event_start->toDateTimeString();
        $eventAttributes['event_end_utc'] = $event_end->toDateTimeString();

        $this->assertDatabaseHas('events', $eventAttributes);
        $redirectTo = $response->getTargetUrl();
        $p = strrpos($redirectTo, '/');
        $idevents = substr($redirectTo, $p + 1);

        return $idevents;
    }

    public function createDevice($idevents, $type)
    {
        $deviceAttributes = Device::factory()->{lcfirst($type)}()->raw();

        $deviceAttributes['event_id'] = $idevents;
        $deviceAttributes['quantity'] = 1;

        $response = $this->post('/device/create', $deviceAttributes);
        $iddevices = Device::latest()->first()->iddevices;
        $this->assertNotNull($iddevices);

        return $iddevices;
    }

    public function createJane()
    {
        $user = User::factory()->create([
            'name' => 'Jane Bloggs',
            'email' => 'jane@bloggs.net',
            'password' => Hash::make('passw0rd'),
            'role' => Role::ADMINISTRATOR,
            'consent_gdpr' => true,
            'consent_future_data' => true,
            'repairdir_role' => Role::REPAIR_DIRECTORY_SUPERADMIN,
        ]);

        $user->save();
    }

    public function getVueProperties($response)
    {
        $crawler = new Crawler($response->getContent());

        $props = [];

        $classname = 'vue';
        $vues = $crawler->filterXPath("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]")->each(function (Crawler $node, $i) {
            return $node;
        });

        foreach ($vues as $vue) {
            foreach ($vue->children() as $child) {
                $dom = simplexml_import_dom($child);
                $atts = current($dom->attributes());
                $atts = $atts ? $atts : [];

                $props[] = array_merge($atts, [
                    'VueComponent' => $vue->children()->first()->nodeName(),
                ]);
            }
        }

        return $props;
    }

    private function canonicalise($val)
    {
        // Sinple code to filter out timestamps or other random values.
        if ($val && is_string($val)) {
            $val = preg_replace('/"created_at":".*?"/', '"created_at":"TIMESTAMP"', $val);
            $val = preg_replace('/"updated_at":".*?"/', '"updated_at":"TIMESTAMP"', $val);
            $val = preg_replace('/"shareable_code":".*?"/', '"shareable_code":"SHARECODE"', $val);
        }

        return $val;
    }

    private function isJson2($string)
    {
        // We have our own version because the PHPUnit one returns TRUE for a simple string.
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function canonicaliseAndAssertSame($val1, $val2, $name)
    {
        $val1 = $this->canonicalise($val1);
        $val2 = $this->canonicalise($val2);

        if ($this->isJson2($val1) && $this->isJson2($val2)) {
            // We get nicer mismatch display if we compare the decoded JSON object rather than comparing the
            // string encoding.
            $dec1 = json_decode($val1, true);
            $dec2 = json_decode($val2, true);

            $this->assertSame($dec1, $dec2, $name);
        } else {
            $this->assertSame($val1, $val2, $name);
        }
    }

    public function assertVueProperties($response, $expected)
    {
        // Assert that the returned response has some properties passed to our Vue components.
        //
        // phpunit has assertArraySubset, but this is controversially being removed in later versions so don't rely
        // on it.
        $props = $this->getVueProperties($response);
        $foundSome = false;

        for ($i = 0; $i < count($expected); $i++) {
            foreach ($expected[$i] as $key => $value) {
                $this->assertArrayHasKey($key, $props[$i]);
                $this->canonicaliseAndAssertSame($value, $props[$i][$key], $key);
                $foundSome = true;
            }
        }

        $this->assertTrue($foundSome);

        return $props;
    }

    public function processQueuedNotifications() {
        // Process queued notifications.
        while (Queue::size() > 0) {
            Artisan::call('queue:work', ['--once' => true]);
        }
    }
}

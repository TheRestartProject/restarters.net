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
use App\Alert;
use Auth;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\DomCrawler\Crawler;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Osteel\OpenApi\Testing\Exceptions\ValidationException;
use ReflectionFunction;
use Illuminate\Events\Dispatcher;

abstract class TestCase extends BaseTestCase
{

    private $userCount = 0;
    public $groupCount = 0;
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
        Alert::truncate();
        DB::statement('delete from audits');
        DB::delete('delete from user_network');
        DB::delete('delete from grouptags_groups');
        DB::delete('delete from failed_jobs');
        DB::table('notifications')->truncate();
        DB::statement('SET foreign_key_checks=1');

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

        if (!Network::where('name', 'Restarters')->first()) {
            $network = new Network();
            $network->name = 'Restarters';
            $network->shortname = 'restarters';
            $network->save();
        }

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
        $this->OpenAPIValidator = ValidatorBuilder::fromJson(storage_path('api-docs/api-docs.json'))->getValidator();

        // Some tests may override the queue.
        $queueManager = $this->app['queue'];
        $queueManager->setDefaultDriver('database');

        // Clear any jobs queued in earlier tests.
        $max = 1000;
        do {
            $job = Queue::pop('database');

            if ($job) {
                try {
                    $job->fail('removed in UT');
                } catch (\Exception $e) {}
            }

            $max--;
        }
        while (Queue::size() > 0 && $max > 0);
    }

    public function userAttributes()
    {
        // Return a test user.
        $userAttributes = [];
        $userAttributes['name'] = 'Test'.uniqid($this->userCount++, true);
        $userAttributes['email'] = $userAttributes['name'].'@restarters.dev';
        $userAttributes['age'] = '1982';
        $userAttributes['country'] = 'GB';
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

    public function createGroup($name = 'Test Group', $website = 'https://therestartproject.org', $location = 'London', $text = 'Some text.', $assert = true, $approve = true, $email = null)
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
             'timezone' => 'Europe/London',
             'network_data' => [
                 'dummy' => 'dummy',
             ],
            'email' => $email,
        ]);

        if ($assert) {
            $this->assertTrue($this->lastResponse->isSuccessful());
            $json = json_decode($this->lastResponse->getContent(), true);
            $this->assertTrue(array_key_exists('id', $json));
            $idgroups = $json['id'];

            if ($approve) {
                $group = Group::find($idgroups);
                $group->approved = true;
                $group->save();
            }

            // Currently logged in user should be present, with status 1 = approved.
            $member = UserGroups::where('group', $idgroups)->first();
            $this->assertEquals(1, $member->status);
            $this->assertEquals(Role::HOST, $member->role);
            $this->assertEquals(Auth::user()->id, $member->user);
        }

        return $idgroups;
    }

    public function createEvent($idgroups, $date, $assert = true, $approve = false)
    {
        // Create a party for the specific group.
        $eventAttributes = Party::factory()->raw();
        $eventAttributes['group'] = $idgroups;

        $event_start = Carbon::createFromTimestamp(strtotime($date))->setTimezone('UTC');
        $event_end = Carbon::createFromTimestamp(strtotime($date))->setTimezone('UTC')->addHour(2);

        // We create groups using the API.
        $user = Auth::user();

        $this->lastResponse = $this->post('/api/v2/events?api_token=' . $user->api_token, [
            'groupid' => $idgroups,
            'start' => $event_start->toIso8601String(),
            'end' => $event_end->toIso8601String(),
            'title' => $eventAttributes['venue'],
            'location' => $eventAttributes['location'],
            'description' => $eventAttributes['free_text'],
            'timezone' => $eventAttributes['timezone']
        ]);

        if ($assert) {
            $this->assertTrue($this->lastResponse->isSuccessful());
            $json = json_decode($this->lastResponse->getContent(), true);
            $this->assertTrue(array_key_exists('id', $json));
            $idevents = $json['id'];

            if ($approve) {
                $party = Party::findOrFail($idevents);
                $party->approved = true;
                $party->save();
            }

            // Currently logged in user should be present, with status 1 = approved.
            $member = EventsUsers::where('event', $idevents)->first();
            $this->assertEquals(1, $member->status);
            $this->assertEquals(Role::HOST, $member->role);
            $this->assertEquals($user->id, $member->user);
        }

        // Need to reformat start/end for row comparison.
        $eventAttributes['event_start_utc'] = $event_start->toDateTimeString();
        $eventAttributes['event_end_utc'] = $event_end->toDateTimeString();

        $this->assertDatabaseHas('events', $eventAttributes);

        return $idevents;
    }

    public function createDevice($idevents, $type, $barrierstr = null, $age = 1.5, $estimate = 100, $problem = '', $repair_status = NULL, $next_steps = NULL, $spare_parts = NULL, $category = NULL)
    {
        // Many tests use $type to create a device from DeviceFactory.
        $deviceAttributes = Device::factory()->{lcfirst($type)}()->raw();

        if (array_key_exists('problem', $deviceAttributes)) {
            $problem = $deviceAttributes['problem'];
        }

        // The v2 API takes the repair stats as a string
        if (!$repair_status) {
            $rs = array_key_exists('repair_status', $deviceAttributes) ? $deviceAttributes['repair_status'] : Device::REPAIR_STATUS_REPAIRABLE;

            switch ($rs) {
                case Device::REPAIR_STATUS_FIXED:
                    $repair_status = Device::REPAIR_STATUS_FIXED_STR;
                    break;
                case Device::REPAIR_STATUS_REPAIRABLE:
                    $repair_status = Device::REPAIR_STATUS_REPAIRABLE_STR;
                    break;
                case Device::REPAIR_STATUS_ENDOFLIFE:
                    $repair_status = Device::REPAIR_STATUS_ENDOFLIFE_STR;
                    break;
                default:
                    $this->assertTrue(false);
            }
        }

        $params = [
            'eventid' => $idevents,
            'category' => $deviceAttributes['category'],
            'problem' =>  $problem,
            'notes' => 'Test notes',
            'brand' => 'Test brand',
            'model' => 'Test model',
            'age' => $age,
            'estimate' => $estimate,
            'item_type' => 'Test item type',
            'repair_status' => $repair_status,
            'barrier' => $barrierstr,
        ];

        if ($next_steps) {
            $params['next_steps'] = $next_steps;
        }

        if ($spare_parts) {
            $params['spare_parts'] = $spare_parts;
        }

        if ($category) {
            $params['category'] = $category;
        }

        $response = $this->post('/api/v2/devices', $params);

        $this->assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
        $iddevices = $json['id'];
        $this->assertNotNull($iddevices);

        return $iddevices;
    }

    public function getDevice($iddevices) {
        $response = $this->get("/api/v2/devices/$iddevices");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $atts = $json['data'];
        return $atts;
    }

    public function deleteDevice($iddevices)
    {
        $response = $this->delete("/api/v2/devices/$iddevices");
        $this->assertTrue($response->isSuccessful());
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

    // We override the methods to make HTTP requests so that we can automatically validate them against our OpenAPI
    // definition where appropriate.
    public function get($uri, array $headers = [])
    {
        $response = parent::get($uri, $headers);

        if (strpos($uri, '/api/v2') === 0) {
            // Validate response against OpenAPI schema.
            $result = $this->OpenAPIValidator->validate($response->baseResponse, $uri, 'get');
            $this->assertTrue($result);
        }

        return $response;
    }

    public function patch($uri, array $params = [], $headers = [])
    {
        $response = parent::patch($uri, $params, $headers);

        if (strpos($uri, '/api/v2') === 0) {
            // Validate response against OpenAPI schema.
            $result = $this->OpenAPIValidator->validate($response->baseResponse, $uri, 'patch');
            $this->assertTrue($result);
        }

        return $response;
    }

    public function post($uri, array $params = [], $headers = [])
    {
        $response = parent::post($uri, $params, $headers);

        if (strpos($uri, '/api/v2') === 0) {
            // Validate response against OpenAPI schema.
            $result = $this->OpenAPIValidator->validate($response->baseResponse, $uri, 'post');
            $this->assertTrue($result);
        }

        return $response;
    }

    /**
     * Convert the internal attribute names to the names used in the v2 API.  We have this because some of the tests
     * use getAttributes() for convenience, which returns the internal attribute names, and then calls the API with
     * them.
     *
     * @param $atts
     * @return void
     */

    public function eventAttributesToAPI($atts) {
        $atts['title'] = $atts['venue'];
        $atts['description'] = $atts['free_text'];

        if (array_key_exists('event_start_utc', $atts)) {
            $atts['start'] = Carbon::parse($atts['event_start_utc'])->setTimezone('UTC')->toIso8601String();
            $atts['end'] = Carbon::parse($atts['event_end_utc'])->setTimezone('UTC')->toIso8601String();
        } else {
            // Fake an event that's two hours long.  This is necessary because PartyFactory->raw() doesn't
            // invoke afterMaking.
            $faker = \Faker\Factory::create();
            $start = Carbon::parse($faker->iso8601());
            $end = $start;
            $end->addHours(2);
            $atts['start'] = $start->toIso8601String();
            $atts['end'] = $end->toIso8601String();
        }

        $atts['groupid'] = $atts['group'];

        return $atts;
    }

    public function assertListenerIsAttachedToEvent($listener, $event)
    {
        $dispatcher = app(Dispatcher::class);

        foreach ($dispatcher->getListeners(is_object($event) ? get_class($event) : $event) as $listenerClosure) {
            $reflection = new ReflectionFunction($listenerClosure);
            $listenerClass = $reflection->getStaticVariables()['listener'];

            if ($listenerClass === $listener) {
                $this->assertTrue(true);

                return;
            }
        }

        $this->assertTrue(false, sprintf('Event %s does not have the %s listener attached to it', $event, $listener));
    }
}

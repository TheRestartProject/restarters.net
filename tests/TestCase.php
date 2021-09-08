<?php

namespace Tests;

use App\Audits;
use App\Brands;
use App\Category;
use App\Device;
use App\EventsUsers;
use App\Group;
use App\GroupNetwork;
use App\GroupTags;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;
use Auth;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
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
        Party::truncate();
        UserGroups::truncate();
        Device::truncate();
        GroupNetwork::truncate();
        Category::truncate();
        Brands::truncate();
        GroupTags::truncate();
        DB::statement('delete from audits');
        DB::delete('delete from user_network');
        DB::delete('delete from grouptags_groups');
        DB::table('notifications')->truncate();
        DB::statement('SET foreign_key_checks=1');

        $network = new Network();
        $network->name = 'Restarters';
        $network->shortname = 'restarters';
        $network->save();

        $this->withoutExceptionHandling();
        app('honeypot')->disable();

        // We don't yet have a Discourse test environment.
        config(['restarters.features.discourse_integration' => false]);

        factory(Category::class, 1)->states('Cat1')->create();
        factory(Category::class, 1)->states('Cat2')->create();
        factory(Category::class, 1)->states('Cat3')->create();
        factory(Category::class, 1)->states('Mobile')->create();
        factory(Category::class, 1)->states('Misc')->create();
        factory(Category::class, 1)->states('Desktop computer')->create();
    }

    public function userAttributes()
    {
        // Return a test user.
        $userAttributes = [];
        $userAttributes['name'] = 'Test'.$this->userCount++;
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
        $response = $this->post('/user/register/', $this->userAttributes($role));

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');

        // Set the role.
        Auth::user()->role = $role;
    }

    public function createGroup($name = 'Test Group', $website = 'https://therestartproject.org', $location = 'London', $text = 'Some text.', $assert = true)
    {
        $idgroups = null;

        $this->lastResponse = $this->post('/group/create', [
            'name' => $name.$this->groupCount++,
            'website' => $website,
            'location' => $location,
            'free_text' => $text,
        ]);

        if ($assert) {
            $this->assertTrue($this->lastResponse->isRedirection());
            $redirectTo = $this->lastResponse->getTargetUrl();
            $this->assertNotFalse(strpos($redirectTo, '/group/edit'));
            $p = strrpos($redirectTo, '/');
            $idgroups = substr($redirectTo, $p + 1);
        }

        return $idgroups;
    }

    public function createEvent($idgroups, $date)
    {
        // Create a party for the specific group.
        $eventAttributes = factory(Party::class)->raw();
        $eventAttributes['group'] = $idgroups;

        $eventAttributes['event_date'] = date('Y-m-d', strtotime($date));

        $response = $this->post('/party/create/', $eventAttributes);
        $this->assertDatabaseHas('events', $eventAttributes);
        $redirectTo = $response->getTargetUrl();
        $p = strrpos($redirectTo, '/');
        $idevents = substr($redirectTo, $p + 1);

        return $idevents;
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

    public function createJane()
    {
        $user = factory(User::class)->create([
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

                $props[] = array_merge(current($dom->attributes()), [
                    'VueComponent' => $vue->children()->first()->nodeName(),
                ]);
            }
        }

        return $props;
    }

    private function canonicalise($val)
    {
        // Sinple code to filter out timestamps.
        if ($val && is_string($val)) {
            $val = preg_replace('/"created_at":".*"/', '"created_at":"TIMESTAMP"', $val);
            $val = preg_replace('/"updated_at":".*"/', '"updated_at":"TIMESTAMP"', $val);
        }

        return $val;
    }

    private function isJson2($string) {
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

    public function setDiscourseTestEnvironment()
    {
        // TODO I feel this isn't really necessary.
        config(['restarters.features.discourse_integration' => true]);
    }
}

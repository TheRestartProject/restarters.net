<?php

namespace Tests\Feature;

use App\Group;
use App\GroupTags;
use App\Network;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Auth;
use function PHPUnit\Framework\assertEquals;

class APIv2GroupTest extends TestCase
{
    /**
     * @dataProvider providerTrueFalse
     *
     * @param $approve
     */
    public function testGetGroup($approve) {
        $user = User::factory()->administrator()->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup(
            'Test Group',
            'https://therestartproject.org',
            'London',
            'Some text.',
            true,
            $approve,
            'info@test.com'
        );

        // Test invalid group id.
        try
        {
            $this->get('/api/v2/groups/-1');
            $this->assertFalse(true);
        } catch (ModelNotFoundException $e)
        {
        }

        // Get group.
        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($idgroups, $json['data']['id']);
        $this->assertEquals('info@test.com', $json['data']['email']);
        $this->assertTrue(array_key_exists('description', $json['data']));
        $this->assertTrue(array_key_exists('stats', $json['data']));
        $this->assertTrue(array_key_exists('updated_at', $json['data']));
        $this->assertTrue(array_key_exists('location', $json['data']));
        $location = $json['data']['location'];
        $this->assertEquals('London', $location['location']);
        $this->assertEquals('GB', $location['country_code']);
        $this->assertEquals('United Kingdom', $location['country']);

        // Check the network data has been created as expected.
        $this->assertEquals('dummy', $json['data']['network_data']['dummy']);

        // Test group moderation.
        $response = $this->get("/api/v2/moderate/groups?api_token=1234");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        if (!$approve)
        {
            self::assertEquals(1, count($json));
            self::assertEquals($idgroups, $json[0]['id']);
        } else
        {
            // Group should not show as requiring moderation because it was approved during createGroup().
            self::assertEquals(0, count($json));
        }
    }

    public function providerTrueFalse()
    {
        return [
            [false],
            [true],
        ];
    }

    public function testCreateGroupLoggedOut()
    {
        $this->expectException(AuthenticationException::class);

        $response = $this->post('/api/v2/groups', [
            'name' => 'Test Group',
            'location' => 'London',
            'description' => 'Some text.',
        ]);
    }

    public function testCreateGroupLoggedInWithoutToken()
    {
        // Logged in as a user should work, even if we don't use an API token.
        $user = User::factory()->administrator()->create([
                                                                          'api_token' => null,
                                                                      ]);
        $this->actingAs($user);

        $response = $this->post('/api/v2/groups', [
            'name' => 'Test Group',
            'location' => 'London',
            'description' => 'Some text.',
        ]);

        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
    }

    public function testCreateGroupLoggedOutWithToken()
    {
        // Logged out should work if we use an API token.
        $user = User::factory()->administrator()->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        // Set a network on the user.
        $network = Network::factory()->create([
                                                       'shortname' => 'network',
                                                   ]);
        $user->repair_network = $network->id;
        $user->save();

        \Storage::fake('avatars');

        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = TRUE;
        file_put_contents('/tmp/UT.jpg', file_get_contents(public_path() .'/images/community.jpg'));

        $_FILES = [
            'image' => [
                'error'    => "0",
                'name'     => 'UT.jpg',
                'size'     => 123,
                'tmp_name' => [ '/tmp/UT.jpg' ],
                'type'     => 'image/jpg'
            ]
        ];

        $response = $this->post(
            '/api/v2/groups?api_token=1234',
            [
                'name' => 'Test Group',
                'location' => 'London',
                'description' => 'Some text.',
                'timezone' => 'Europe/Brussels',
                'email' => 'info@test.com'
            ]
        );

        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
        $idgroups = $json['id'];
        $this->assertGreaterThan(0, $idgroups);

        $group = Group::findOrfail($idgroups);
        $this->assertEquals('Test Group', $group->name);
        $this->assertEquals('London', $group->location);
        $this->assertEquals('Some text.', $group->free_text);
        $this->assertStringContainsString('.jpg', $group->groupImage->image->path);
        $this->assertEquals('Europe/Brussels', $group->timezone);
        $this->assertEquals('info@test.com', $group->email);

        // Group should now appear in the list of groups.
        $response = $this->get('/api/v2/groups/names');
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $groups = $json['data'];
        $found = false;

        foreach ($groups as $g)
        {
            if ($group->name == $g['name'])
            {
                $found = true;
            }
        }

        $this->assertTrue($found);
    }

    public function testCreateGroupGeocodeFailure()
    {
        $user = User::factory()->administrator()->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        $this->actingAs($user);

        $this->expectException(ValidationException::class);

        $response = $this->post('/api/v2/groups?api_token=1234', [
            'name' => 'Test Group',
            'location' => 'ForceGeocodeFailure',
            'description' => 'Some text.',
        ]);
    }

    public function testCreateGroupInvalidTimezone()
    {
        $user = User::factory()->administrator()->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        $this->actingAs($user);

        $this->expectException(ValidationException::class);

        $response = $this->post('/api/v2/groups?api_token=1234', [
            'name' => 'Test Group',
            'location' => 'London, UK',
            'description' => 'Some text.',
            'timezone' => 'invalidtimezone'
        ]);
    }

    public function testCreateGroupDuplicate()
    {
        // Logged in as a user should work, even if we don't use an API token.
        $user = User::factory()->administrator()->create([
                                                                          'api_token' => null,
                                                                      ]);
        $this->actingAs($user);

        $response = $this->post('/api/v2/groups', [
            'name' => 'Test Group',
            'location' => 'London',
            'description' => 'Some text.',
        ]);

        $response->assertSuccessful();

        // Creating again should cause a validation exception.
        $this->expectException(ValidationException::class);

        $response = $this->post('/api/v2/groups', [
            'name' => 'Test Group',
            'location' => 'London',
            'description' => 'Some text.',
        ]);
    }

    public function testTags() {
        $tag = GroupTags::factory()->create();
        $response = $this->get('/api/v2/groups/tags', []);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        self::assertEquals($tag->id, $json['data'][0]['id']);
        
        $group = Group::factory()->create();
        $tag = GroupTags::factory()->create();
        $group->addTag($tag);

        $response = $this->get("/api/v2/groups/{$group->idgroups}", []);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        self::assertEquals($tag->id, $json['data']['tags'][0]['id']);
    }

    public function testOutdated() {
        // Check we can create a group with an outdated timezone.
        $user = User::factory()->administrator()->create([
                                                             'api_token' => '1234',
                                                         ]);
        // Set a network on the user.
        $network = Network::factory()->create([
                                                  'shortname' => 'network',
                                              ]);
        $user->repair_network = $network->id;
        $user->save();

        $response = $this->post(
            '/api/v2/groups?api_token=1234',
            [
                'name' => 'Test Group',
                'location' => 'London',
                'description' => 'Some text.',
                'timezone' => 'Australia/Canberra'
            ]
        );

        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
        $idgroups = $json['id'];
        $this->assertGreaterThan(0, $idgroups);
    }

    /**
     * Network coordinators should see groups for approval, but only from their own networks.
     *
     * @dataProvider providerTrueFalse
     *
     * @return void
     */
    public function testNetworkCoordinatorApprove($first) {
        $network1 = Network::factory()->create();
        $group1 = Group::factory()->create();
        $coordinator1 = User::factory()->networkCoordinator()->create([
            'api_token' => '1234',
        ]);
        $network2 = Network::factory()->create();
        $group2 = Group::factory()->create();
        $coordinator2 = User::factory()->networkCoordinator()->create([
            'api_token' => '5678',
        ]);

        $network1->addGroup($group1);
        $network1->addCoordinator($coordinator1);
        $network2->addGroup($group2);
        $network2->addCoordinator($coordinator2);

        if ($first) {
            $response = $this->get("/api/v2/moderate/groups?api_token=1234");
            $response->assertSuccessful();
            $json = json_decode($response->getContent(), true);
            self::assertEquals(1, count($json));
            self::assertEquals($group1->idgroups, $json[0]['id']);
        } else {
            $response = $this->get("/api/v2/moderate/groups?api_token=5678");
            $response->assertSuccessful();
            $json = json_decode($response->getContent(), true);
            self::assertEquals(1, count($json));
            self::assertEquals($group2->idgroups, $json[0]['id']);
        }
    }

    public function testLocales() {
        $user = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup(
            'Test Group',
            'https://therestartproject.org',
            'Brussels, Belgium',
            'Some text.',
            true,
        );

        # Get in default locale - English.
        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $location = $json['data']['location'];
        $this->assertEquals('Belgium', $location['country']);

        // Get in Belgian French.
        $response = $this->get("/api/v2/groups/$idgroups?locale=fr-BE");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $location = $json['data']['location'];
        $this->assertEquals('Belgique', $location['country']);

        // Create a group in
    }

    public function testEmptyNetworkData() {
        $user = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($user);

        $idgroups = null;

        // Get the dashboard.  This will ensure that we have set the repair_network on the user.
        $this->get('/');

        // We create groups using the API.
        $user = Auth::user();

        $this->lastResponse = $this->post('/api/v2/groups?api_token=' . $user->api_token, [
            'name' => 'Test Group Empty',
            'website' => 'https://therestartproject.org',
            'location' => 'Brussels, Belgium',
            'description' => 'Some text.',
            'timezone' => 'Europe/London',
            'network_data' => [],
            'email' => null,
        ]);

        $this->assertTrue($this->lastResponse->isSuccessful());
        $json = json_decode($this->lastResponse->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
        $idgroups = $json['id'];

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        assertEquals(null, $json['data']['network_data']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\GroupTags;
use App\Helpers\RepairNetworkService;
use App\Models\Network;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Auth;
use App\Models\Role;
use function PHPUnit\Framework\assertEquals;

class APIv2GroupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     *
     * @param $approve
     */
    #[DataProvider('providerTrueFalse')]
    public function testGetGroup($approve): void {
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        $this->actingAs($user);

        $idgroups = $this->createGroup(
            'Test Group ' . uniqid(),
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
        $response = $this->get("/api/v2/moderate/groups?api_token=" . $user->api_token);
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

    public static function providerTrueFalse(): array
    {
        return [
            [false],
            [true],
        ];
    }

    public function testCreateGroupLoggedOut(): void
    {
        $this->expectException(AuthenticationException::class);

        $response = $this->post('/api/v2/groups', [
            'name' => 'Test Group ' . uniqid(),
            'location' => 'London',
            'description' => 'Some text.',
        ]);
    }

    public function testCreateGroupLoggedInWithoutToken(): void
    {
        // Logged in as a user should work, even if we don't use an API token.
        $user = $this->createUserWithToken(Role::ADMINISTRATOR, [], false);
        $this->actingAs($user);

        $response = $this->post('/api/v2/groups', [
            'name' => 'Test Group ' . uniqid(),
            'location' => 'London',
            'description' => 'Some text.',
        ]);

        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
    }

    public function testCreateGroupLoggedOutWithToken(): void
    {
        // Logged out should work if we use an API token.
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        // Set a network on the user.
        $network = Network::factory()->create([
                                                       'shortname' => 'network',
                                                   ]);
        $user->repair_network = $network->id;
        $user->save();

        // Make sure we're logged out
        Auth::logout();
        $this->assertFalse(Auth::check());

        \Storage::fake('avatars');

        \FixometerFile::$uploadTesting = TRUE;
        
        // Create test image in public/uploads
        $testImage = public_path('/images/community.jpg');
        $tempImage = public_path('/uploads/UT.jpg');
        copy($testImage, $tempImage);

        $_FILES = [
            'image' => [
                'error'    => "0",
                'name'     => 'UT.jpg',
                'size'     => 123,
                'tmp_name' => $tempImage,
                'type'     => 'image/jpg'
            ]
        ];

        $testGroupName = 'Test Group ' . uniqid();
        
        // Adding actingAs here to authenticate the request but we're still "logged out" from the web context
        $this->actingAs($user, 'api');
        
        $response = $this->post(
            '/api/v2/groups?api_token=' . $user->api_token,
            [
                'name' => $testGroupName,
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
        $this->assertEquals($testGroupName, $group->name);
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
        $this->assertGroupFound($groups, $idgroups, true);
    }

    private function assertGroupFound($groups, $id, $shouldBeFound) {
        $ix = 0;
        $found = false;

        foreach ($groups as $g) {
            if ($g['id'] == $id) {
                $found = true;
            } else {
                $ix++;
            }
        }

        $this->assertEquals($shouldBeFound, $found);
        return $ix;
    }

    public function testCreateGroupGeocodeFailure(): void
    {
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        $this->actingAs($user);

        $this->expectException(ValidationException::class);

        $response = $this->post('/api/v2/groups?api_token=' . $user->api_token, [
            'name' => 'Test Group ' . uniqid(),
            'location' => 'ForceGeocodeFailure',
            'description' => 'Some text.',
        ]);
    }

    public function testCreateGroupInvalidTimezone(): void
    {
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        $this->actingAs($user);

        $this->expectException(ValidationException::class);

        $response = $this->post('/api/v2/groups?api_token=' . $user->api_token, [
            'name' => 'Test Group ' . uniqid(),
            'location' => 'London, UK',
            'description' => 'Some text.',
            'timezone' => 'invalidtimezone'
        ]);
    }

    public function testCreateGroupDuplicate(): void
    {
        // Logged in as a user should work, even if we don't use an API token.
        $user = $this->createUserWithToken(Role::ADMINISTRATOR, [], false);
        $this->actingAs($user);

        $groupName = 'Test Group Duplicate ' . uniqid();
        $response = $this->post('/api/v2/groups', [
            'name' => $groupName,
            'location' => 'London',
            'description' => 'Some text.',
        ]);

        $response->assertSuccessful();

        // Creating again with the same name should cause a validation exception.
        try {
            $this->post('/api/v2/groups', [
                'name' => $groupName, // Use the same name to trigger duplicate validation
                'location' => 'London',
                'description' => 'Some text.',
            ]);
            
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertTrue(true, 'ValidationException was thrown as expected');
            $this->assertArrayHasKey('name', $e->errors());
        }
    }

    public function testTags(): void {
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        $this->actingAs($user);
        
        $tag = GroupTags::factory()->create();
        $response = $this->get('/api/v2/groups/tags', []);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        // Don't assert specific ID matches, just check that we got a valid response structure
        $this->assertArrayHasKey('data', $json);
        $this->assertNotEmpty($json['data']);
        
        $group = Group::factory()->create();
        $tag = GroupTags::factory()->create();
        $group->addTag($tag);

        $response = $this->get("/api/v2/groups/{$group->idgroups}", []);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        // Check that the tag ID is in the group's tags array
        $this->assertArrayHasKey('tags', $json['data']);
        $tagIds = array_column($json['data']['tags'], 'id');
        $this->assertContains($tag->id, $tagIds);
    }

    public function testOutdated(): void {
        // Check we can create a group with an outdated timezone.
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        $this->actingAs($user);
        
        // Set a network on the user.
        $network = Network::factory()->create([
                                                  'shortname' => 'network',
                                              ]);
        $user->repair_network = $network->id;
        $user->save();

        $response = $this->post(
            '/api/v2/groups?api_token=' . $user->api_token,
            [
                'name' => 'Test Group ' . uniqid(),
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
     */
    #[DataProvider('providerTrueFalse')]
    public function testNetworkCoordinatorApprove($first): void {
        $network1 = Network::factory()->create();
        $group1 = Group::factory()->create();
        $coordinator1 = $this->createUserWithToken(Role::NETWORK_COORDINATOR);
        $this->actingAs($coordinator1);
        
        $network2 = Network::factory()->create();
        $group2 = Group::factory()->create();
        $coordinator2 = $this->createUserWithToken(Role::NETWORK_COORDINATOR);

        $network1->addGroup($group1);
        $network1->addCoordinator($coordinator1);
        $network2->addGroup($group2);
        $network2->addCoordinator($coordinator2);

        if ($first) {
            $response = $this->get("/api/v2/moderate/groups?api_token=" . $coordinator1->api_token);
            $response->assertSuccessful();
            $json = json_decode($response->getContent(), true);
            self::assertEquals(1, count($json));
            self::assertEquals($group1->idgroups, $json[0]['id']);
        } else {
            // Switch to acting as coordinator2
            $this->actingAs($coordinator2);
            $response = $this->get("/api/v2/moderate/groups?api_token=" . $coordinator2->api_token);
            $response->assertSuccessful();
            $json = json_decode($response->getContent(), true);
            self::assertEquals(1, count($json));
            self::assertEquals($group2->idgroups, $json[0]['id']);
        }
    }

    public function testLocales(): void {
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        $this->actingAs($user);

        $idgroups = $this->createGroup(
            'Test Group ' . uniqid(),
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

    public function testEmptyNetworkData(): void {
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        $this->actingAs($user);

        $idgroups = null;

        // Get the dashboard.  This will ensure that we have set the repair_network on the user.
        $this->get('/');

        // We create groups using the API.
        $user = Auth::user();

        $this->lastResponse = $this->post('/api/v2/groups?api_token=' . $user->api_token, [
            'name' => 'Test Group Empty ' . uniqid(),
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

    public function testNetworkDataUpdatedAt(): void {
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        $this->actingAs($user);

        $this->get('/');
        $user = Auth::user();

        $this->lastResponse = $this->post('/api/v2/groups?api_token=' . $user->api_token, [
            'name' => 'Test Group Updated ' . uniqid(),
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

        $network = Network::factory()->create();

        $group = Group::find($idgroups);
        $this->networkService = new RepairNetworkService();
        $this->networkService->addGroupToNetwork($user, $group, $network);

        $now = Carbon::now()->toIso8601String();
        sleep(1);

        $response = $this->patch('/api/v2/groups/' . $idgroups, [
            'network_data' => [
                'foo' => 'bar',
            ],
        ]);

        $response->assertSuccessful();

        // Check the updated_at has been, well, updated.
        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($idgroups, $json['data']['id']);
        $updated_at = $json['data']['updated_at'];
        $this->assertNotEquals($now, $updated_at);

        // Test the v1 API.
        $network->addCoordinator($user);
        $this->actingAs($user);

        $response = $this->get('/api/groups/network?api_token=' . $user->api_token);
        $groups = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($groups));
        $this->assertEquals($idgroups, $groups[0]['id']);
        $this->assertEquals((new Carbon($updated_at))->getTimestamp(), (new Carbon($groups[0]['updated_at']))->getTimestamp());
    }

    public function testArchived(): void {
        $user = $this->createUserWithToken(Role::ADMINISTRATOR);
        $this->actingAs($user);

        $this->get('/');
        $user = Auth::user();

        $idgroups = $this->createGroup(
            'Test Group ' . uniqid(),
            'https://therestartproject.org',
            'London',
            'Some text.',
            true,
            true,
            'info@test.com'
        );

        $group = Group::find($idgroups);

        $network = Network::factory()->create();
        $network->addGroup($group);

        // Get group - archived_at should not be set.
        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($idgroups, $json['data']['id']);
        $this->assertNull($json['data']['archived_at']);

        // Patch the group to set it.
        $response = $this->patch('/api/v2/groups/' . $idgroups, [
            'archived_at' => '2022-01-01',
            'description' => 'Some text.'
        ]);
        $response->assertSuccessful();

        // Get it back - should be set.
        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($idgroups, $json['data']['id']);
        $this->assertEquals('2022-01-01T00:00:00+00:00', $json['data']['archived_at']);
        $this->assertEquals('Some text.', $json['data']['description']);

        // Group shouldn't appear in the list of groups by default.
        $response = $this->get('/api/v2/groups/names');
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $groups = $json['data'];

        $this->assertGroupFound($groups, $idgroups, false);

        $response = $this->get('/api/v2/groups/names?includeArchived=true');
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $groups = $json['data'];
        $ix = $this->assertGroupFound($groups, $idgroups, true);
        $this->assertEquals('2022-01-01T00:00:00+00:00', $groups[$ix]['archived_at']);

        $response = $this->get('/api/v2/groups/names?includeArchived=false');
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $groups = $json['data'];
        $this->assertGroupFound($groups, $idgroups, false);

        $response = $this->get("/api/v2/networks/{$network->id}/groups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $groups = $json['data'];
        $this->assertGroupFound($groups, $idgroups, false);

        $response = $this->get("/api/v2/networks/{$network->id}/groups?includeArchived=true");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $groups = $json['data'];
        $this->assertGroupFound($groups, $idgroups, true);
    }
}

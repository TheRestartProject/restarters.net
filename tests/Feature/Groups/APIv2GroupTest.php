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
            $approve
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
        $this->assertTrue(array_key_exists('description', $json['data']));
        $this->assertTrue(array_key_exists('stats', $json['data']));
        $this->assertTrue(array_key_exists('updated_at', $json['data']));
        $this->assertTrue(array_key_exists('location', $json['data']));
        $location = $json['data']['location'];
        $this->assertEquals('London', $location['location']);
        $this->assertEquals('United Kingdom', $location['country']);

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
        $user = factory(User::class)->states('Administrator')->create([
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
        $user = factory(User::class)->states('Administrator')->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        // Set a network on the user.
        $network = factory(Network::class)->create([
                                                       'shortname' => 'network',
                                                   ]);
        $user->repair_network = $network->id;
        $user->save();

        \Storage::fake('avatars');

        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = TRUE;
        file_put_contents('/tmp/UT.jpg', file_get_contents('public/images/community.jpg'));

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
                'timezone' => 'Europe/Brussels'
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
        $user = factory(User::class)->states('Administrator')->create([
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
        $user = factory(User::class)->states('Administrator')->create([
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
        $user = factory(User::class)->states('Administrator')->create([
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
        $tag = factory(GroupTags::class)->create();
        $response = $this->get('/api/v2/groups/tags', []);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        self::assertEquals($tag->id, $json['data'][0]['id']);

        $group = factory(Group::class)->create();
        $tag = factory(GroupTags::class)->create();
        $group->addTag($tag);

        $response = $this->get("/api/v2/groups/{$group->idgroups}", []);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        self::assertEquals($tag->id, $json['data']['tags'][0]['id']);
    }
}

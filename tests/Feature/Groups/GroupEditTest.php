<?php

namespace Tests\Feature;

use App\Group;
use App\GroupTags;
use App\Role;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class GroupEditTest extends TestCase
{
    /** @test */
    public function group_tags_retained_after_edited_by_host()
    {
        $this->withoutExceptionHandling();

        $group = Group::factory()->create();
        $tag = GroupTags::factory()->create();
        $group->addTag($tag);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        $response = $this->get('/group/edit/' . $group->idgroups);
        $response->assertStatus(200);

        $response = $this->patch('/api/v2/groups/' . $group->idgroups, [
            'description' => 'Test',
            'location' => 'London',
            'name' => 'Test',
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
            'network_data' => [
                'no_dummy' => 'no_dummy'
            ]
        ]);

        $response->assertSuccessful();

        $group->refresh();
        $this->assertEquals(1, count($group->group_tags));
        $this->assertEquals($tag->tag_name, $group->group_tags[0]->tag_name);

        // Network data should have changed.
        $this->assertEquals($group->network_data, [
            'no_dummy' => 'no_dummy'
        ]);
    }

    public function testEditGroupAsRestarter() {
        $group = Group::factory()->create();

        $this->loginAsTestUser(Role::RESTARTER);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get('/group/edit/' . $group->idgroups);
    }

    /** @test */
    public function invalid_location()
    {
        $this->withoutExceptionHandling();

        $group = Group::factory()->create();
        $tag = GroupTags::factory()->create();
        $group->addTag($tag);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        $this->expectException(ValidationException::class);

        $this->patch('/api/v2/groups/' . $group->idgroups, [
            'description' => 'Test',
            'location' => 'zzzzzzzzzzzzz1234',
            'name' => 'Test',
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
        ]);
    }

    /** @test */
    public function image_upload() {
        Storage::fake('avatars');
        $group = Group::factory()->create();

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        // We don't upload files in a standard Laravel way, so testing upload is a bit of a hack.
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = TRUE;
        file_put_contents('/tmp/UT.jpg', file_get_contents('public/images/community.jpg'));

        $_FILES = [
            'file' => [
                'error'    => "0",
                'name'     => 'UT.jpg',
                'size'     => 123,
                'tmp_name' => [ '/tmp/UT.jpg' ],
                'type'     => 'image/jpg'
            ]
        ];

        $response = $this->json('POST', '/group/image-upload/' . $group->idgroups, []);
        $response->assertOk();
        $this->assertEquals('success - image uploaded', $response->getContent());

        // And again, which will test the case of overwriting.
        $response = $this->json('POST', '/group/image-upload/' . $group->idgroups, []);
        $response->assertOk();
        $this->assertEquals('success - image uploaded', $response->getContent());

        // Delete the image.
        $image = \DB::select(
        \Illuminate\Support\Facades\DB::raw("SELECT idimages, path FROM images ORDER BY idimages DESC LIMIT 1"));
        $idimages = $image[0]->idimages;
        $path = $image[0]->path;
        $response = $this->get("/group/image/delete/{$group->idgroups}/$idimages/$path");
        $response->assertOk();
        self::assertEquals('Thank you, the image has been deleted', $response->getContent());
    }

    /** @test */
    public function can_edit_timezone() {
        // Get list of timezones.
        $response = $this->get('/api/timezones');
        $response->assertSuccessful();
        $timezones = json_decode($response->getContent(), TRUE);
        self::assertGreaterThan(0, count($timezones));
        self::assertTrue(array_key_exists('name', $timezones[0]));

        // Australia/Canberra is an outdated timezone; check it appears.
        $found = false;
        foreach ($timezones as $timezone) {
            if ($timezone['name'] == 'Australia/Canberra') {
                $found = true;
            }
        }

        self::assertTrue($found);
    }
}

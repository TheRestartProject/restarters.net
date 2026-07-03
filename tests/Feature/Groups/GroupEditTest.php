<?php

namespace Tests\Feature;

use App\Group;
use App\GroupTags;
use App\Network;
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
    public function group_tags_retained_after_edited_by_host(): void
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

    public function testEditGroupAsRestarter(): void {
        $group = Group::factory()->create();

        $this->loginAsTestUser(Role::RESTARTER);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get('/group/edit/' . $group->idgroups);
    }

    /** @test */
    public function invalid_location(): void
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
    public function image_upload(): void {
        Storage::fake('avatars');
        $group = Group::factory()->create();

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        // We don't upload files in a standard Laravel way, so testing upload is a bit of a hack.
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = TRUE;
        file_put_contents('/tmp/UT.jpg', file_get_contents(public_path() . '/images/community.jpg'));

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
        $image = \DB::select("SELECT idimages, path FROM images ORDER BY idimages DESC LIMIT 1");
        $idimages = $image[0]->idimages;
        $path = $image[0]->path;
        $response = $this->get("/group/image/delete/{$group->idgroups}/$idimages/$path");
        $response->assertOk();
        self::assertEquals('Thank you, the image has been deleted', $response->getContent());
    }

    /** @test */
    public function image_upload_preserves_existing_image_when_upload_fails(): void {
        $group = Group::factory()->create();
        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = true;

        if (!is_dir(getcwd() . '/uploads')) {
            mkdir(getcwd() . '/uploads', 0777, true);
        }

        file_put_contents('/tmp/UT_preserve.jpg', file_get_contents(public_path() . '/images/community.jpg'));

        // First upload via API (the path the Vue component uses)
        $_FILES = [
            'image' => [
                'error'    => "0",
                'name'     => 'UT_preserve.jpg',
                'size'     => 123,
                'tmp_name' => ['/tmp/UT_preserve.jpg'],
                'type'     => 'image/jpeg'
            ]
        ];

        $response = $this->patch('/api/v2/groups/' . $group->idgroups, [
            'description' => 'Test',
            'location'    => 'London',
            'name'        => 'Test',
        ]);
        $response->assertSuccessful();

        $xrefCount = \DB::table('xref')
            ->where('reference', $group->idgroups)
            ->where('reference_type', env('TBL_GROUPS'))
            ->count();
        $this->assertEquals(1, $xrefCount, 'Should have one image after first upload');

        // Now simulate a failed upload — non-existent directory (like Fly.io with no uploads dir)
        $_SERVER['DOCUMENT_ROOT'] = '/tmp/nonexistent_upload_dir_' . uniqid();
        file_put_contents('/tmp/UT_preserve2.jpg', file_get_contents(public_path() . '/images/community.jpg'));
        $_FILES = [
            'image' => [
                'error'    => "0",
                'name'     => 'UT_preserve2.jpg',
                'size'     => 123,
                'tmp_name' => ['/tmp/UT_preserve2.jpg'],
                'type'     => 'image/jpeg'
            ]
        ];

        $this->patch('/api/v2/groups/' . $group->idgroups, [
            'description' => 'Test',
            'location'    => 'London',
            'name'        => 'Test',
        ]);

        // The old image must still exist — it should NOT be deleted when the new upload fails
        $xrefCountAfterFail = \DB::table('xref')
            ->where('reference', $group->idgroups)
            ->where('reference_type', env('TBL_GROUPS'))
            ->count();
        $this->assertEquals(1, $xrefCountAfterFail, 'Old image must be preserved when new upload fails');

        // Clean up
        $_FILES = [];
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
    }

    /** @test */
    public function can_edit_timezone(): void {
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

    /** @test */
    public function edit_email(): void
    {
        $this->withoutExceptionHandling();

        $group = Group::factory()->create();

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
            ],
            'email' => 'info@test.com'
        ]);

        $response->assertSuccessful();

        $group->refresh();
        $this->assertEquals('info@test.com', $group->email);
    }

    public function testEditAsNetworkCoordinator(): void {
        $network = Network::factory()->create();
        $coordinator = User::factory()->restarter()->create();
        $network->addCoordinator($coordinator);
        $coordinator->refresh();
        $this->actingAs($coordinator);

        $idgroups = $this->createGroup(
            'Test Group',
            'https://therestartproject.org',
            'London',
            'Some text.',
            true,
            false,
            'info@test.com'
        );

        $response = $this->get('/group/edit/' . $idgroups);
        $response->assertStatus(200);

        // Shouldn't be able to approve the group, as it has not yet been put in our network (by an admin).
        $this->assertVueProperties($response, [
            [],
            [
                ':can-approve' => 'false',
            ],
        ]);
    }

    /** @test */
    // F005: stored XSS via audited model attributes rendered in the audit-log accordion.
    public function audit_log_escapes_xss_payload_in_group_fields(): void
    {
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $group = Group::factory()->create(['website' => 'https://safe.example.com']);

        // Updating an audited field records an 'updated' audit holding the new value verbatim.
        $group->website = "<script>alert('XSSPROBE')</script>";
        $group->save();

        $response = $this->get('/group/edit/' . $group->idgroups);
        $response->assertStatus(200);

        // The injected <script> must NOT reach the admin's browser unescaped...
        $response->assertDontSee("<script>alert('XSSPROBE')", false);
        // ...but the value must still be displayed, HTML-escaped, in the audit log.
        // (Blade's {{ }} uses ENT_QUOTES, so < > become &lt; &gt; and ' becomes &#039;.)
        $response->assertSee('&lt;script&gt;alert(', false);
        $response->assertSee('XSSPROBE', false);
    }
}

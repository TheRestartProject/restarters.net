<?php

namespace Tests\Feature;

use App\Group;
use App\Network;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The image_upload feature flag exists so that preview/staging environments
 * sharing the production Tigris bucket can disable writes to it.
 */
class UploadsDisabledTest extends TestCase
{
    /** @test */
    public function image_upload_flag_defaults_to_enabled(): void
    {
        $this->assertTrue(config('restarters.features.image_upload'));
    }

    /** @test */
    public function group_image_upload_rejected_when_uploads_disabled(): void
    {
        config(['restarters.features.image_upload' => false]);

        $group = Group::factory()->create();
        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = true;
        file_put_contents('/tmp/UT_disabled.jpg', file_get_contents(public_path() . '/images/community.jpg'));

        $_FILES = [
            'file' => [
                'error' => '0',
                'name' => 'UT_disabled.jpg',
                'size' => 123,
                'tmp_name' => ['/tmp/UT_disabled.jpg'],
                'type' => 'image/jpg',
            ],
        ];

        $imagesBefore = \DB::table('images')->count();

        $response = $this->json('POST', '/group/image-upload/' . $group->idgroups, []);
        $response->assertOk();
        $this->assertStringStartsWith('fail - image could not be uploaded', $response->getContent());
        $this->assertStringContainsString('disabled', $response->getContent());

        $this->assertEquals($imagesBefore, \DB::table('images')->count());
    }

    /** @test */
    public function group_image_upload_allowed_when_uploads_enabled(): void
    {
        config(['restarters.features.image_upload' => true]);

        $group = Group::factory()->create();
        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = true;
        file_put_contents('/tmp/UT_enabled.jpg', file_get_contents(public_path() . '/images/community.jpg'));

        $_FILES = [
            'file' => [
                'error' => '0',
                'name' => 'UT_enabled.jpg',
                'size' => 123,
                'tmp_name' => ['/tmp/UT_enabled.jpg'],
                'type' => 'image/jpg',
            ],
        ];

        $response = $this->json('POST', '/group/image-upload/' . $group->idgroups, []);
        $response->assertOk();
        $this->assertEquals('success - image uploaded', $response->getContent());
    }

    /** @test */
    public function network_logo_upload_rejected_when_uploads_disabled(): void
    {
        config(['restarters.features.image_upload' => false]);
        Storage::fake('public_uploads');

        $network = Network::factory()->create();
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->put(route('networks.update', $network), [
            'network_logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertRedirect(route('networks.edit', $network));
        $response->assertSessionHas('warning');
        $this->assertNull($network->fresh()->logo);
    }

    /** @test */
    public function network_logo_upload_allowed_when_uploads_enabled(): void
    {
        config(['restarters.features.image_upload' => true]);
        Storage::fake('public_uploads');

        $network = Network::factory()->create();
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->put(route('networks.update', $network), [
            'network_logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertRedirect(route('networks.edit', $network));
        $logo = $network->fresh()->logo;
        $this->assertNotNull($logo);
        $this->assertStringStartsWith('network_logos/', $logo);
    }
}

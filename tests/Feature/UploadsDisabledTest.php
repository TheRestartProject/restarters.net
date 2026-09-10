<?php

namespace Tests\Feature;

use App\Group;
use App\Network;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
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

        $imagesBefore = \DB::table('images')->count();

        $response = $this->postGroupImage('UT_disabled.jpg');
        $response->assertOk();
        $this->assertStringStartsWith('fail - image could not be uploaded', $response->getContent());
        $this->assertStringContainsString('disabled', $response->getContent());

        $this->assertEquals($imagesBefore, \DB::table('images')->count());
    }

    /** @test */
    public function group_image_upload_allowed_when_uploads_enabled(): void
    {
        config(['restarters.features.image_upload' => true]);

        $response = $this->postGroupImage('UT_enabled.jpg');
        $response->assertOk();
        $this->assertEquals('success - image uploaded', $response->getContent());
    }

    /** @test */
    public function network_logo_upload_rejected_when_uploads_disabled(): void
    {
        config(['restarters.features.image_upload' => false]);

        [$network, $response] = $this->putNetworkLogo();

        $response->assertRedirect(route('networks.edit', $network));
        $response->assertSessionHas('warning');
        $this->assertNull($network->fresh()->logo);
    }

    /** @test */
    public function network_logo_upload_allowed_when_uploads_enabled(): void
    {
        config(['restarters.features.image_upload' => true]);

        [$network, $response] = $this->putNetworkLogo();

        $response->assertRedirect(route('networks.edit', $network));
        $logo = $network->fresh()->logo;
        $this->assertNotNull($logo);
        $this->assertStringStartsWith('network_logos/', $logo);
    }

    /**
     * Post an image to the group image-upload endpoint as a host, using the
     * legacy $_FILES mechanism the endpoint expects.
     */
    private function postGroupImage(string $filename): TestResponse
    {
        $group = Group::factory()->create();
        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = true;
        file_put_contents('/tmp/' . $filename, file_get_contents(public_path() . '/images/community.jpg'));

        $_FILES = [
            'file' => [
                'error' => '0',
                'name' => $filename,
                'size' => 123,
                'tmp_name' => ['/tmp/' . $filename],
                'type' => 'image/jpg',
            ],
        ];

        return $this->json('POST', '/group/image-upload/' . $group->idgroups, []);
    }

    /**
     * Upload a network logo as an administrator.
     *
     * @return array{0: Network, 1: TestResponse}
     */
    private function putNetworkLogo(): array
    {
        Storage::fake('public_uploads');

        $network = Network::factory()->create();
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->put(route('networks.update', $network), [
            'network_logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        return [$network, $response];
    }
}

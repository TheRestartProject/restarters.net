<?php

namespace Tests\Feature;

use App\Group;
use App\Helpers\Tus;
use App\User;
use Tests\TestCase;
use TusPhp\Cache\Cacheable;

/**
 * The image_upload feature flag exists so that preview/staging environments
 * sharing the production Tigris bucket can disable writes to it. The flag is
 * enforced in FixometerFile::uploadLocalFile, which every image endpoint
 * (group/event/device) funnels through, so disabling it makes the API image
 * upload fail.
 *
 * Network-logo upload is not (yet) exposed over /api/v2 — the old
 * PUT /networks/{network} web route was removed at the Nuxt cutover, so the
 * two network-logo cases were dropped (see cutover-checklist GAPS).
 */
class UploadsDisabledTest extends TestCase
{
    /** @var string[] */
    private array $tmpTusFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpTusFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        parent::tearDown();
    }

    /** @test */
    public function image_upload_flag_defaults_to_enabled(): void
    {
        $this->assertTrue(config('restarters.features.image_upload'));
    }

    /** @test */
    public function group_image_upload_rejected_when_uploads_disabled(): void
    {
        // The base TestCase disables exception handling; re-enable it so the
        // rejection surfaces as a 422 response rather than a raised exception.
        $this->withExceptionHandling();

        config(['restarters.features.image_upload' => false]);

        $imagesBefore = \DB::table('images')->count();

        $response = $this->postGroupImage();
        // FixometerFile refuses the write, so uploadImagev2 rejects the upload.
        $response->assertStatus(422);
        $this->assertEquals($imagesBefore, \DB::table('images')->count());
    }

    /** @test */
    public function group_image_upload_allowed_when_uploads_enabled(): void
    {
        config(['restarters.features.image_upload' => true]);

        $response = $this->postGroupImage();
        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => ['image_url']]);
    }

    /**
     * Upload a group image through the API as a host of the group, staging a
     * completed tus upload the way the SPA uploader would.
     */
    private function postGroupImage(): \Illuminate\Testing\TestResponse
    {
        $group = Group::factory()->create();
        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        // FixometerFile::uploadLocalFile writes relative to DOCUMENT_ROOT.
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = true;
        $key = $this->seedCompletedTusUpload(public_path().'/images/community.jpg');

        // v2 endpoints authenticate via the token guard (auth:sanctum,api).
        return $this->postJson('/api/v2/groups/'.$group->idgroups.'/images?api_token='.$host->api_token, ['upload_key' => $key]);
    }

    private function seedCompletedTusUpload(string $sourcePath): string
    {
        $key = 'ud-'.uniqid();

        $uploadDir = Tus::uploadDir();
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $destPath = $uploadDir.'/'.$key;
        copy($sourcePath, $destPath);
        $this->tmpTusFiles[] = $destPath;

        $size = filesize($destPath);
        $cache = Tus::buildCache();
        $now = new \DateTime();

        $cache->set($key, [
            'name' => $key,
            'size' => $size,
            'offset' => $size,
            'checksum' => null,
            'location' => 'http://localhost/api/tus/'.$key,
            'file_path' => $destPath,
            'metadata' => [],
            'upload_type' => 'normal',
            'created_at' => $now->format(Cacheable::RFC_7231),
            'expires_at' => $now->modify('+1 day')->format(Cacheable::RFC_7231),
        ]);

        return $key;
    }
}

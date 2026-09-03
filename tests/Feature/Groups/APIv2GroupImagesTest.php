<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Helpers\Tus;
use App\User;
use App\Xref;
use Illuminate\Support\Str;
use TusPhp\Cache\Cacheable;
use Tests\TestCase;

class APIv2GroupImagesTest extends TestCase
{
    /**
     * Files we drop into the real tus upload dir for a test - cleaned up in tearDown()
     * regardless of whether the endpoint itself deletes them. Same approach as
     * Tests\Feature\Users\APIv2UserPhotoTest, adapted for group images.
     */
    private array $tmpTusFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = true;
    }

    protected function tearDown(): void
    {
        \FixometerFile::$uploadTesting = false;

        foreach ($this->tmpTusFiles as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    private function seedCompletedTusUpload(string $sourcePath, ?int $offsetOverride = null): string
    {
        $key = 'gi-'.uniqid();

        $uploadDir = Tus::uploadDir();
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $destPath = $uploadDir.'/'.$key;
        copy($sourcePath, $destPath);
        $this->tmpTusFiles[] = $destPath;

        $size = filesize($destPath);
        $offset = $offsetOverride ?? $size;

        $cache = Tus::buildCache();
        $now = new \DateTime();

        $cache->set($key, [
            'name' => $key,
            'size' => $size,
            'offset' => $offset,
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

    private function tmpJpeg(): string
    {
        $tmpPath = '/tmp/GI-'.uniqid().'.jpg';
        file_put_contents($tmpPath, file_get_contents(public_path().'/images/community.jpg'));
        $this->tmpTusFiles[] = $tmpPath;

        return $tmpPath;
    }

    private function createGroupAsHost(User $host): int
    {
        $this->actingAs($host);

        return $this->createGroup(
            'Image Test Group '.Str::random(8),
            'https://therestartproject.org',
            'London'
        );
    }

    // --- upload ---

    public function testUploadRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->postJson("/api/v2/groups/$idgroups/images", ['upload_key' => 'whatever']);

        $response->assertStatus(401);
    }

    public function testUploadDeniedForRestarterWithNoPermission(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'gimg-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-1", ['upload_key' => $key]);

        $response->assertStatus(403);
    }

    public function testUploadMissingUploadKeyReturns422(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-2']);
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-2", []);

        $response->assertStatus(422);
    }

    public function testUploadUnknownUploadKeyReturns422(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-3']);
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-3", [
            'upload_key' => 'this-key-does-not-exist',
        ]);

        $response->assertStatus(422);
    }

    public function testUploadRejectsNonImageUpload(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-4']);
        $idgroups = $this->createGroupAsHost($host);

        $tmpPath = '/tmp/GI-'.uniqid().'.txt';
        file_put_contents($tmpPath, 'this is not an image');
        $this->tmpTusFiles[] = $tmpPath;

        $key = $this->seedCompletedTusUpload($tmpPath);

        $response = $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-4", ['upload_key' => $key]);

        $response->assertStatus(422);
    }

    public function testUploadRejectsOversizedUpload(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-5']);
        $idgroups = $this->createGroupAsHost($host);

        $tmpPath = '/tmp/GI-'.uniqid().'.jpg';
        file_put_contents($tmpPath, str_repeat('0', 3 * 1024 * 1024).file_get_contents(public_path().'/images/community.jpg'));
        $this->tmpTusFiles[] = $tmpPath;

        $key = $this->seedCompletedTusUpload($tmpPath);

        $response = $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-5", ['upload_key' => $key]);

        $response->assertStatus(422);
    }

    public function testUploadRejectsIncompleteUpload(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-6']);
        $idgroups = $this->createGroupAsHost($host);

        $tmpPath = $this->tmpJpeg();
        $key = $this->seedCompletedTusUpload($tmpPath, (int) floor(filesize($tmpPath) / 2));

        $response = $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-6", ['upload_key' => $key]);

        $response->assertStatus(422);
    }

    public function testUploadUnknownGroupReturns404(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-404']);
        $this->actingAs($host);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/groups/999999/images?api_token=gimg-tok-404", ['upload_key' => $key]);

        $response->assertStatus(404);
    }

    public function testSuccessfulUploadAttachesImageAndReturnsUrl(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-7']);
        $idgroups = $this->createGroupAsHost($host);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-7", ['upload_key' => $key]);

        $response->assertSuccessful();
        $this->assertNotEmpty($response->json('data.image_url'));

        $group = Group::find($idgroups);
        $this->assertNotNull($group->groupImage);
    }

    public function testUploadKeyCannotBeReplayed(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-8']);
        $idgroups = $this->createGroupAsHost($host);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $first = $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-8", ['upload_key' => $key]);
        $first->assertSuccessful();

        $second = $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-8", ['upload_key' => $key]);
        $second->assertStatus(422);
    }

    public function testUploadingAgainReplacesThePreviousImage(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-9']);
        $idgroups = $this->createGroupAsHost($host);

        $firstKey = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-9", ['upload_key' => $firstKey])->assertSuccessful();

        $group = Group::find($idgroups);
        $firstXref = $group->groupImage->idxref;

        $secondKey = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-9", ['upload_key' => $secondKey])->assertSuccessful();

        $group->refresh();
        $this->assertNotEquals($firstXref, $group->groupImage->idxref);
        $this->assertNull(Xref::find($firstXref));
    }

    // --- delete ---

    public function testDeleteRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-10']);
        $idgroups = $this->createGroupAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-10", ['upload_key' => $key])->assertSuccessful();
        $idxref = Group::find($idgroups)->groupImage->idxref;

        // forgetGuards is the codebase's identity-switch convention; Auth::logout()
        // would explode when the cached default guard is a TokenGuard.
        $this->app['auth']->forgetGuards();

        $response = $this->deleteJson("/api/v2/groups/$idgroups/images/$idxref");

        $response->assertStatus(401);
    }

    public function testDeleteDeniedForRestarterWithNoPermission(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-11']);
        $idgroups = $this->createGroupAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-11", ['upload_key' => $key])->assertSuccessful();
        $idxref = Group::find($idgroups)->groupImage->idxref;

        $restarter = User::factory()->restarter()->create(['api_token' => 'gimg-tok-12']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->deleteJson("/api/v2/groups/$idgroups/images/$idxref?api_token=gimg-tok-12");

        $response->assertStatus(403);
        $this->assertNotNull(Xref::find($idxref));
    }

    public function testDeleteUnknownImageReturns404(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-13']);
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->deleteJson("/api/v2/groups/$idgroups/images/999999?api_token=gimg-tok-13");

        $response->assertStatus(404);
    }

    public function testHostCanDeleteGroupImage(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-14']);
        $idgroups = $this->createGroupAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-14", ['upload_key' => $key])->assertSuccessful();
        $idxref = Group::find($idgroups)->groupImage->idxref;

        $response = $this->deleteJson("/api/v2/groups/$idgroups/images/$idxref?api_token=gimg-tok-14");

        $response->assertSuccessful();
        $this->assertEquals(['deleted' => true], $response->json('data'));
        $this->assertNull(Xref::find($idxref));
        $this->assertNull(Group::find($idgroups)->groupImage);
    }

    public function testCannotDeleteAnotherGroupsImageByGuessingTheXrefId(): void
    {
        $hostA = User::factory()->host()->create(['api_token' => 'gimg-tok-15']);
        $idgroupsA = $this->createGroupAsHost($hostA);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/groups/$idgroupsA/images?api_token=gimg-tok-15", ['upload_key' => $key])->assertSuccessful();
        $idxrefA = Group::find($idgroupsA)->groupImage->idxref;

        $hostB = User::factory()->host()->create(['api_token' => 'gimg-tok-16']);
        $this->app['auth']->forgetGuards();
        $idgroupsB = $this->createGroupAsHost($hostB);

        // hostB is a host of group B, not group A - deleting group A's image xref via group B's
        // URL must not succeed even though hostB legitimately has edit rights on SOME group.
        $response = $this->deleteJson("/api/v2/groups/$idgroupsB/images/$idxrefA?api_token=gimg-tok-16");

        $response->assertStatus(404);
        $this->assertNotNull(Xref::find($idxrefA));
    }

    // --- image_idxref exposure on the group resource (gap 4: without this the client has no id
    // to pass to DELETE /api/v2/groups/{id}/images/{idimages}, so the delete button is dead) ---

    public function testGroupResourceExposesImageIdxrefAfterUpload(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'gimg-tok-17']);
        $idgroups = $this->createGroupAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/groups/$idgroups/images?api_token=gimg-tok-17", ['upload_key' => $key])->assertSuccessful();

        $idxref = Group::find($idgroups)->groupImage->idxref;

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();

        $this->assertEquals($idxref, $response->json('data.image_idxref'));
        $this->assertNotEmpty($response->json('data.image'));
    }

    public function testGroupResourceImageIdxrefNullWithNoImage(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();

        $this->assertNull($response->json('data.image_idxref'));
        $this->assertNull($response->json('data.image'));
    }
}

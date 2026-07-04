<?php

namespace Tests\Feature\Users;

use App\Helpers\Tus;
use App\User;
use TusPhp\Cache\Cacheable;
use Tests\TestCase;

class APIv2UserPhotoTest extends TestCase
{
    /**
     * Files we drop into the real tus upload dir for a test - cleaned up in tearDown()
     * regardless of whether the endpoint itself deletes them.
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

    /**
     * Simulating a full Uppy -> tus-php HTTP resumable upload from a PHPUnit test is
     * impractical (it's a multi-request, chunked binary protocol). Instead we exercise
     * the REAL production code path from the point a tus upload has completed: we use
     * the same App\Helpers\Tus::buildCache() factory updateMyPhotov2() uses, drop an
     * assembled file into the real tus upload dir, and write a cache entry in the exact
     * shape TusPhp\Tus\Server::handlePatch() leaves behind once offset===size (see
     * TusPhp\File::details() - name/size/offset/file_path/created_at/expires_at).
     * This means updateMyPhotov2() itself is exercised unmodified/unmocked - only the
     * wire protocol that gets a file onto disk is stood in for. A separate end-to-end
     * test (APIv2TusUploadTest) drives the real HTTP tus route so the protocol itself
     * is also covered.
     */
    private function seedCompletedTusUpload(string $sourcePath, ?int $offsetOverride = null): string
    {
        $key = 'ut-' . uniqid();

        $uploadDir = Tus::uploadDir();
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $destPath = $uploadDir . '/' . $key;
        copy($sourcePath, $destPath);
        $this->tmpTusFiles[] = $destPath;

        $size = filesize($destPath);
        $offset = $offsetOverride ?? $size; // Complete by default: offset === size.

        $cache = Tus::buildCache();
        $now = new \DateTime();

        $cache->set($key, [
            'name' => $key,
            'size' => $size,
            'offset' => $offset,
            'checksum' => null,
            'location' => 'http://localhost/api/tus/' . $key,
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
        $tmpPath = '/tmp/UT-' . uniqid() . '.jpg';
        file_put_contents($tmpPath, file_get_contents(public_path() . '/images/community.jpg'));
        $this->tmpTusFiles[] = $tmpPath;

        return $tmpPath;
    }

    public function testRequiresAuth(): void
    {
        $response = $this->postJson('/api/v2/users/me/photo', ['upload_key' => 'whatever']);
        $response->assertStatus(401);
    }

    public function testMissingUploadKeyReturns422(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'photo-tok-1']);
        $this->actingAs($user);

        $response = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-1', []);

        $response->assertStatus(422);
    }

    public function testUnknownUploadKeyReturns422(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'photo-tok-2']);
        $this->actingAs($user);

        $response = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-2', [
            'upload_key' => 'this-key-does-not-exist-in-the-tus-cache',
        ]);

        $response->assertStatus(422);

        $profile = User::getProfile($user->id);
        $this->assertNull($profile->path ?? null);
    }

    public function testRejectsNonImageUpload(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'photo-tok-3']);
        $this->actingAs($user);

        $tmpPath = '/tmp/UT-' . uniqid() . '.txt';
        file_put_contents($tmpPath, 'this is not an image, just plain text');
        $this->tmpTusFiles[] = $tmpPath;

        $key = $this->seedCompletedTusUpload($tmpPath);

        $response = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-3', ['upload_key' => $key]);

        $response->assertStatus(422);

        $profile = User::getProfile($user->id);
        $this->assertNull($profile->path ?? null);
    }

    public function testRejectsOversizedUpload(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'photo-tok-4']);
        $this->actingAs($user);

        $tmpPath = '/tmp/UT-' . uniqid() . '.jpg';
        file_put_contents($tmpPath, str_repeat('0', 3 * 1024 * 1024) . file_get_contents(public_path() . '/images/community.jpg'));
        $this->tmpTusFiles[] = $tmpPath;

        $key = $this->seedCompletedTusUpload($tmpPath);

        $response = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-4', ['upload_key' => $key]);

        $response->assertStatus(422);
    }

    public function testRejectsIncompleteUpload(): void
    {
        // offset !== size -> the tus upload hasn't finished yet, must not be usable.
        $user = User::factory()->host()->create(['api_token' => 'photo-tok-5']);
        $this->actingAs($user);

        $tmpPath = $this->tmpJpeg();
        $key = $this->seedCompletedTusUpload($tmpPath, (int) floor(filesize($tmpPath) / 2));

        $response = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-5', ['upload_key' => $key]);

        $response->assertStatus(422);
    }

    public function testSuccessfulUploadStoresImageAndReturnsPath(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'photo-tok-6']);
        $this->actingAs($user);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-6', ['upload_key' => $key]);

        $response->assertSuccessful();

        $path = $response->json('data.path');
        $this->assertNotEmpty($path);

        // The photo should now be retrievable via the standard profile lookup used
        // elsewhere in the app (User::getProfile()).
        $profile = User::getProfile($user->id);
        $this->assertEquals($path, $profile->path);
    }

    public function testUploadKeyCannotBeReplayed(): void
    {
        // Once consumed, the tus temp file + cache entry should be gone - the same
        // upload_key must not be attachable twice.
        $user = User::factory()->host()->create(['api_token' => 'photo-tok-7']);
        $this->actingAs($user);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $first = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-7', ['upload_key' => $key]);
        $first->assertSuccessful();

        $second = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-7', ['upload_key' => $key]);
        $second->assertStatus(422);
    }

    public function testResponseHidesPii(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'photo-tok-8']);
        $this->actingAs($user);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-8', ['upload_key' => $key]);

        $response->assertSuccessful();

        $json = $response->getContent();
        $this->assertStringNotContainsString('api_token', $json);
        $this->assertStringNotContainsString('"password"', $json);
    }

    public function testCannotUploadPhotoForAnotherUser(): void
    {
        // The endpoint always operates on Auth::user() (no id param) - confirm the
        // uploaded photo attaches to the acting user, not any id smuggled into the body.
        $attacker = User::factory()->restarter()->create(['api_token' => 'photo-tok-9']);
        $victim = User::factory()->restarter()->create(['api_token' => 'photo-tok-10']);
        $this->actingAs($attacker);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson('/api/v2/users/me/photo?api_token=photo-tok-9', [
            'upload_key' => $key,
            'id' => $victim->id,
        ]);

        $response->assertSuccessful();

        $victimProfile = User::getProfile($victim->id);
        $this->assertNull($victimProfile->path ?? null);

        $attackerProfile = User::getProfile($attacker->id);
        $this->assertNotNull($attackerProfile->path);
    }
}

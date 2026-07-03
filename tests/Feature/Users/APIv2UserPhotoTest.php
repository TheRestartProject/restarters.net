<?php

namespace Tests\Feature\Users;

use App\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class APIv2UserPhotoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = true;
    }

    protected function tearDown(): void
    {
        $_FILES = [];
        \FixometerFile::$uploadTesting = false;
        parent::tearDown();
    }

    /**
     * FixometerFile::upload() reads the raw $_FILES superglobal directly (a pre-existing
     * codebase convention - see tests/Feature/Devices/EditTest.php and
     * tests/Feature/Groups/APIv2GroupTest.php for the same pattern), while our new
     * endpoint's own validation reads Laravel's $request->file() bag. So a test upload
     * needs BOTH populated consistently: $_FILES for FixometerFile, and an UploadedFile
     * passed via TestCase::call()'s $files argument for Laravel's validator.
     */
    private function fakeUpload(string $tmpPath, string $originalName, string $mimeType, string $error = '0'): UploadedFile
    {
        $_FILES = [
            'profilePhoto' => [
                'error' => $error,
                'name' => $originalName,
                'size' => filesize($tmpPath),
                'tmp_name' => [$tmpPath],
                'type' => $mimeType,
            ],
        ];

        return new UploadedFile($tmpPath, $originalName, $mimeType, null, true);
    }

    private function tmpJpeg(): string
    {
        $tmpPath = '/tmp/UT-' . uniqid() . '.jpg';
        file_put_contents($tmpPath, file_get_contents(public_path() . '/images/community.jpg'));

        return $tmpPath;
    }

    /**
     * TestCase::call() does not send an Accept: application/json header by default,
     * so a validation failure would render as an HTML redirect (302) rather than the
     * JSON 422 our Handler.php produces for $request->wantsJson(). Force JSON so the
     * API contract is exercised the same way a real API client would see it.
     */
    private function callWithFile(string $uri, UploadedFile $file, array $data = [])
    {
        return $this->call(
            'POST',
            $uri,
            $data,
            [],
            ['profilePhoto' => $file],
            $this->transformHeadersToServerVars(['Accept' => 'application/json'])
        );
    }

    public function testRequiresAuth(): void
    {
        $response = $this->postJson('/api/v2/users/me/photo', []);
        $response->assertStatus(401);
    }

    public function testRejectsNonImageUpload(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $tmpPath = '/tmp/UT-' . uniqid() . '.txt';
        file_put_contents($tmpPath, 'this is not an image, just plain text');

        $file = $this->fakeUpload($tmpPath, 'notanimage.txt', 'text/plain');

        $response = $this->callWithFile('/api/v2/users/me/photo?api_token=tok1', $file);

        $response->assertStatus(422);

        // No image record should have been attached.
        $profile = User::getProfile($user->id);
        $this->assertNull($profile->path ?? null);
    }

    public function testRejectsOversizedUpload(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        // Laravel's "max" validation rule for files reads the size in KB from the
        // UploadedFile - build one that reports over our 2048 KB limit.
        $tmpPath = '/tmp/UT-' . uniqid() . '.jpg';
        file_put_contents($tmpPath, str_repeat('0', 3 * 1024 * 1024) . file_get_contents(public_path() . '/images/community.jpg'));

        $file = $this->fakeUpload($tmpPath, 'big.jpg', 'image/jpeg');

        $response = $this->callWithFile('/api/v2/users/me/photo?api_token=tok1', $file);

        $response->assertStatus(422);
    }

    public function testSuccessfulUploadStoresImageAndReturnsPath(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $file = $this->fakeUpload($this->tmpJpeg(), 'UT.jpg', 'image/jpeg');

        $response = $this->callWithFile('/api/v2/users/me/photo?api_token=tok1', $file);

        $response->assertSuccessful();

        $path = $response->json('data.path');
        $this->assertNotEmpty($path);

        // The photo should now be retrievable via the standard profile lookup used
        // elsewhere in the app (User::getProfile()).
        $profile = User::getProfile($user->id);
        $this->assertEquals($path, $profile->path);
    }

    public function testResponseHidesPii(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $file = $this->fakeUpload($this->tmpJpeg(), 'UT.jpg', 'image/jpeg');

        $response = $this->callWithFile('/api/v2/users/me/photo?api_token=tok1', $file);

        $response->assertSuccessful();

        $json = $response->getContent();
        $this->assertStringNotContainsString('api_token', $json);
        $this->assertStringNotContainsString('"password"', $json);
    }

    public function testCannotUploadPhotoForAnotherUser(): void
    {
        // The endpoint always operates on Auth::user() (no id param) - confirm the
        // uploaded photo attaches to the acting user, not any id smuggled into the body.
        $attacker = User::factory()->restarter()->create(['api_token' => 'tok1']);
        $victim = User::factory()->restarter()->create();
        $this->actingAs($attacker);

        $file = $this->fakeUpload($this->tmpJpeg(), 'UT.jpg', 'image/jpeg');

        $response = $this->callWithFile('/api/v2/users/me/photo?api_token=tok1', $file, ['id' => $victim->id]);

        $response->assertSuccessful();

        $victimProfile = User::getProfile($victim->id);
        $this->assertNull($victimProfile->path ?? null);

        $attackerProfile = User::getProfile($attacker->id);
        $this->assertNotNull($attackerProfile->path);
    }
}

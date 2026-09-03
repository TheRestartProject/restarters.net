<?php

namespace Tests\Feature\Devices;

use App\Device;
use App\Helpers\Tus;
use App\User;
use App\Xref;
use Illuminate\Support\Str;
use TusPhp\Cache\Cacheable;
use Tests\TestCase;

class APIv2DeviceImagesTest extends TestCase
{
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
        $key = 'di-'.uniqid();

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
        $tmpPath = '/tmp/DI-'.uniqid().'.jpg';
        file_put_contents($tmpPath, file_get_contents(public_path().'/images/community.jpg'));
        $this->tmpTusFiles[] = $tmpPath;

        return $tmpPath;
    }

    private function createEventAndDeviceAsHost(User $host): array
    {
        $this->actingAs($host);
        $idgroups = $this->createGroup('Device Image Test Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);
        $iddevices = $this->createDevice($idevents, 'fixed');

        return [$idgroups, $idevents, $iddevices];
    }

    // --- upload ---

    public function testUploadRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        [, , $iddevices] = $this->createEventAndDeviceAsHost($host);

        $response = $this->postJson("/api/v2/devices/$iddevices/images", ['upload_key' => 'whatever']);

        $response->assertStatus(401);
    }

    public function testUploadDeniedForUserWithNoRelationshipToEvent(): void
    {
        $host = User::factory()->host()->create();
        [, , $iddevices] = $this->createEventAndDeviceAsHost($host);

        $stranger = User::factory()->restarter()->create(['api_token' => 'dimg-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($stranger);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/devices/$iddevices/images?api_token=dimg-tok-1", ['upload_key' => $key]);

        $response->assertStatus(403);
    }

    public function testUploadAllowedForConfirmedAttendeeNotJustHost(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents, $iddevices] = $this->createEventAndDeviceAsHost($host);

        $attendee = User::factory()->restarter()->create(['api_token' => 'dimg-tok-2']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($attendee);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=dimg-tok-2")->assertSuccessful();

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/devices/$iddevices/images?api_token=dimg-tok-2", ['upload_key' => $key]);

        $response->assertSuccessful();
    }

    public function testUploadUnknownDeviceReturns404(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dimg-tok-404']);
        $this->actingAs($host);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/devices/999999/images?api_token=dimg-tok-404", ['upload_key' => $key]);

        $response->assertStatus(404);
    }

    public function testSuccessfulUploadAttachesImageAndReturnsUrl(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dimg-tok-3']);
        [, , $iddevices] = $this->createEventAndDeviceAsHost($host);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/devices/$iddevices/images?api_token=dimg-tok-3", ['upload_key' => $key]);

        $response->assertSuccessful();
        $this->assertNotEmpty($response->json('data.image_url'));
        $this->assertEquals(1, Xref::where('reference', $iddevices)->where('reference_type', env('TBL_DEVICES'))->count());
    }

    public function testUploadingAgainAddsToTheGalleryRatherThanReplacing(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dimg-tok-4']);
        [, , $iddevices] = $this->createEventAndDeviceAsHost($host);

        $firstKey = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/devices/$iddevices/images?api_token=dimg-tok-4", ['upload_key' => $firstKey])->assertSuccessful();

        $secondKey = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/devices/$iddevices/images?api_token=dimg-tok-4", ['upload_key' => $secondKey])->assertSuccessful();

        $count = Xref::where('reference', $iddevices)->where('reference_type', env('TBL_DEVICES'))->count();
        $this->assertEquals(2, $count);
    }

    // --- delete ---

    public function testDeleteRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dimg-tok-5']);
        [, , $iddevices] = $this->createEventAndDeviceAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/devices/$iddevices/images?api_token=dimg-tok-5", ['upload_key' => $key])->assertSuccessful();
        $idxref = Xref::where('reference', $iddevices)->where('reference_type', env('TBL_DEVICES'))->first()->idxref;

        $this->app['auth']->forgetGuards();

        $response = $this->deleteJson("/api/v2/devices/$iddevices/images/$idxref");

        $response->assertStatus(401);
    }

    public function testDeleteDeniedForUserWithNoRelationshipToEvent(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dimg-tok-6']);
        [, , $iddevices] = $this->createEventAndDeviceAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/devices/$iddevices/images?api_token=dimg-tok-6", ['upload_key' => $key])->assertSuccessful();
        $idxref = Xref::where('reference', $iddevices)->where('reference_type', env('TBL_DEVICES'))->first()->idxref;

        $stranger = User::factory()->restarter()->create(['api_token' => 'dimg-tok-7']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($stranger);

        $response = $this->deleteJson("/api/v2/devices/$iddevices/images/$idxref?api_token=dimg-tok-7");

        $response->assertStatus(403);
        $this->assertNotNull(Xref::find($idxref));
    }

    public function testDeleteUnknownImageReturns404(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dimg-tok-8']);
        [, , $iddevices] = $this->createEventAndDeviceAsHost($host);

        $response = $this->deleteJson("/api/v2/devices/$iddevices/images/999999?api_token=dimg-tok-8");

        $response->assertStatus(404);
    }

    public function testHostCanDeleteDeviceImage(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dimg-tok-9']);
        [, , $iddevices] = $this->createEventAndDeviceAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/devices/$iddevices/images?api_token=dimg-tok-9", ['upload_key' => $key])->assertSuccessful();
        $idxref = Xref::where('reference', $iddevices)->where('reference_type', env('TBL_DEVICES'))->first()->idxref;

        $response = $this->deleteJson("/api/v2/devices/$iddevices/images/$idxref?api_token=dimg-tok-9");

        $response->assertSuccessful();
        $this->assertEquals(['deleted' => true], $response->json('data'));
        $this->assertNull(Xref::find($idxref));
    }

    public function testCannotDeleteAnotherDevicesImageByGuessingTheXrefId(): void
    {
        $hostA = User::factory()->host()->create(['api_token' => 'dimg-tok-10']);
        [, , $iddevicesA] = $this->createEventAndDeviceAsHost($hostA);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/devices/$iddevicesA/images?api_token=dimg-tok-10", ['upload_key' => $key])->assertSuccessful();
        $idxrefA = Xref::where('reference', $iddevicesA)->where('reference_type', env('TBL_DEVICES'))->first()->idxref;

        $hostB = User::factory()->host()->create(['api_token' => 'dimg-tok-11']);
        $this->app['auth']->forgetGuards();
        [, , $iddevicesB] = $this->createEventAndDeviceAsHost($hostB);

        $response = $this->deleteJson("/api/v2/devices/$iddevicesB/images/$idxrefA?api_token=dimg-tok-11");

        $response->assertStatus(404);
        $this->assertNotNull(Xref::find($idxrefA));
    }
}

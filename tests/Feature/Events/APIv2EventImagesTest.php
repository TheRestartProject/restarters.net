<?php

namespace Tests\Feature\Events;

use App\Party;
use App\User;
use App\Xref;
use App\Helpers\Tus;
use Illuminate\Support\Str;
use TusPhp\Cache\Cacheable;
use Tests\TestCase;

class APIv2EventImagesTest extends TestCase
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
        $key = 'ei-'.uniqid();

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
        $tmpPath = '/tmp/EI-'.uniqid().'.jpg';
        file_put_contents($tmpPath, file_get_contents(public_path().'/images/community.jpg'));
        $this->tmpTusFiles[] = $tmpPath;

        return $tmpPath;
    }

    private function createEventAsHost(User $host): array
    {
        $this->actingAs($host);
        $idgroups = $this->createGroup('Event Image Test Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);

        return [$idgroups, $idevents];
    }

    // --- upload ---

    public function testUploadRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->postJson("/api/v2/events/$idevents/images", ['upload_key' => 'whatever']);

        $response->assertStatus(401);
    }

    public function testUploadDeniedForNonAttendee(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $stranger = User::factory()->restarter()->create(['api_token' => 'eimg-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($stranger);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-1", ['upload_key' => $key]);

        $response->assertStatus(403);
    }

    public function testUploadAllowedForAnyAttendeeNotJustHost(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $attendee = User::factory()->restarter()->create(['api_token' => 'eimg-tok-2']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($attendee);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=eimg-tok-2")->assertSuccessful();

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-2", ['upload_key' => $key]);

        $response->assertSuccessful();
    }

    public function testUploadMissingUploadKeyReturns422(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-3']);
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-3", []);

        $response->assertStatus(422);
    }

    public function testUploadUnknownUploadKeyReturns422(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-4']);
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-4", [
            'upload_key' => 'this-key-does-not-exist',
        ]);

        $response->assertStatus(422);
    }

    public function testUploadRejectsNonImageUpload(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-5']);
        [, $idevents] = $this->createEventAsHost($host);

        $tmpPath = '/tmp/EI-'.uniqid().'.txt';
        file_put_contents($tmpPath, 'this is not an image');
        $this->tmpTusFiles[] = $tmpPath;

        $key = $this->seedCompletedTusUpload($tmpPath);

        $response = $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-5", ['upload_key' => $key]);

        $response->assertStatus(422);
    }

    public function testUploadRejectsOversizedUpload(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-6']);
        [, $idevents] = $this->createEventAsHost($host);

        $tmpPath = '/tmp/EI-'.uniqid().'.jpg';
        file_put_contents($tmpPath, str_repeat('0', 3 * 1024 * 1024).file_get_contents(public_path().'/images/community.jpg'));
        $this->tmpTusFiles[] = $tmpPath;

        $key = $this->seedCompletedTusUpload($tmpPath);

        $response = $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-6", ['upload_key' => $key]);

        $response->assertStatus(422);
    }

    public function testUploadRejectsIncompleteUpload(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-7']);
        [, $idevents] = $this->createEventAsHost($host);

        $tmpPath = $this->tmpJpeg();
        $key = $this->seedCompletedTusUpload($tmpPath, (int) floor(filesize($tmpPath) / 2));

        $response = $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-7", ['upload_key' => $key]);

        $response->assertStatus(422);
    }

    public function testUploadUnknownEventReturns404(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-404']);
        $this->actingAs($host);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/events/999999/images?api_token=eimg-tok-404", ['upload_key' => $key]);

        $response->assertStatus(404);
    }

    public function testSuccessfulUploadAttachesImageAndReturnsUrl(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-8']);
        [, $idevents] = $this->createEventAsHost($host);

        $key = $this->seedCompletedTusUpload($this->tmpJpeg());

        $response = $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-8", ['upload_key' => $key]);

        $response->assertSuccessful();
        $this->assertNotEmpty($response->json('data.image_url'));

        $count = Xref::where('reference', $idevents)->where('reference_type', env('TBL_EVENTS'))->count();
        $this->assertEquals(1, $count);
    }

    public function testUploadingAgainAddsToTheGalleryRatherThanReplacing(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-9']);
        [, $idevents] = $this->createEventAsHost($host);

        $firstKey = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-9", ['upload_key' => $firstKey])->assertSuccessful();

        $secondKey = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-9", ['upload_key' => $secondKey])->assertSuccessful();

        // Unlike group/profile images (single-image semantics), event photos are a gallery - the
        // second upload must not delete the first (see FixometerFile::uploadLocalFile $clear=false).
        $count = Xref::where('reference', $idevents)->where('reference_type', env('TBL_EVENTS'))->count();
        $this->assertEquals(2, $count);
    }

    // --- delete ---

    public function testDeleteRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-10']);
        [, $idevents] = $this->createEventAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-10", ['upload_key' => $key])->assertSuccessful();
        $idxref = Xref::where('reference', $idevents)->where('reference_type', env('TBL_EVENTS'))->first()->idxref;

        $this->app['auth']->forgetGuards();

        $response = $this->deleteJson("/api/v2/events/$idevents/images/$idxref");

        $response->assertStatus(401);
    }

    public function testDeleteDeniedForNonAttendee(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-11']);
        [, $idevents] = $this->createEventAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-11", ['upload_key' => $key])->assertSuccessful();
        $idxref = Xref::where('reference', $idevents)->where('reference_type', env('TBL_EVENTS'))->first()->idxref;

        $stranger = User::factory()->restarter()->create(['api_token' => 'eimg-tok-12']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($stranger);

        $response = $this->deleteJson("/api/v2/events/$idevents/images/$idxref?api_token=eimg-tok-12");

        $response->assertStatus(403);
        $this->assertNotNull(Xref::find($idxref));
    }

    public function testDeleteUnknownImageReturns404(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-13']);
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->deleteJson("/api/v2/events/$idevents/images/999999?api_token=eimg-tok-13");

        $response->assertStatus(404);
    }

    public function testAttendeeCanDeleteEventImage(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'eimg-tok-14']);
        [, $idevents] = $this->createEventAsHost($host);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/events/$idevents/images?api_token=eimg-tok-14", ['upload_key' => $key])->assertSuccessful();
        $idxref = Xref::where('reference', $idevents)->where('reference_type', env('TBL_EVENTS'))->first()->idxref;

        $attendee = User::factory()->restarter()->create(['api_token' => 'eimg-tok-attendee']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($attendee);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=eimg-tok-attendee")->assertSuccessful();

        $response = $this->deleteJson("/api/v2/events/$idevents/images/$idxref?api_token=eimg-tok-attendee");

        $response->assertSuccessful();
        $this->assertEquals(['deleted' => true], $response->json('data'));
        $this->assertNull(Xref::find($idxref));
    }

    public function testCannotDeleteAnotherEventsImageByGuessingTheXrefId(): void
    {
        $hostA = User::factory()->host()->create(['api_token' => 'eimg-tok-15']);
        [, $ideventsA] = $this->createEventAsHost($hostA);
        $key = $this->seedCompletedTusUpload($this->tmpJpeg());
        $this->postJson("/api/v2/events/$ideventsA/images?api_token=eimg-tok-15", ['upload_key' => $key])->assertSuccessful();
        $idxrefA = Xref::where('reference', $ideventsA)->where('reference_type', env('TBL_EVENTS'))->first()->idxref;

        $hostB = User::factory()->host()->create(['api_token' => 'eimg-tok-16']);
        $this->app['auth']->forgetGuards();
        [, $ideventsB] = $this->createEventAsHost($hostB);

        $response = $this->deleteJson("/api/v2/events/$ideventsB/images/$idxrefA?api_token=eimg-tok-16");

        $response->assertStatus(404);
        $this->assertNotNull(Xref::find($idxrefA));
    }
}

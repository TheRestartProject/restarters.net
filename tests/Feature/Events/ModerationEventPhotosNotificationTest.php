<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\Tus;
use App\Notifications\AdminModerationEventPhotos;
use App\Party;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use TusPhp\Cache\Cacheable;

/**
 * Uploading event photos must notify admins (who opted in) that there are
 * photos to moderate. Post-cutover the upload happens via
 * POST /api/v2/events/{id}/images (tus upload_key), not the removed Blade
 * /party/image-upload route; uploadImagev2 fires EventImagesUploaded →
 * SendAdminModerateEventPhotosNotification, exactly as the old controller did.
 */
class ModerationEventPhotosNotificationTest extends TestCase
{
    /** @var User[] */
    protected $admins;

    /** @var User */
    protected $restarter;

    /** @var Party */
    protected $event;

    /** @var Group */
    protected $group;

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

    /**
     * Stage a completed tus upload the way the SPA's uploader would, and
     * return its upload_key. Mirrors APIv2EventImagesTest.
     */
    private function seedCompletedTusUpload(string $sourcePath): string
    {
        $key = 'mod-'.uniqid();

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

    /** @test */
    public function a_moderation_notification_is_sent_to_admins_when_event_photos_are_uploaded(): void
    {
        Notification::fake();

        $this->init_event_and_dependencies();

        // FixometerFile::uploadLocalFile writes relative to DOCUMENT_ROOT.
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = true;
        $key = $this->seedCompletedTusUpload(public_path().'/images/community.jpg');

        // The volunteer uploads a photo through the API. v2 endpoints
        // authenticate via the token guard (auth:sanctum,api), so pass the
        // api_token explicitly.
        $response = $this->actingAs($this->restarter)
                         ->postJson('/api/v2/events/'.$this->event->getKey().'/images?api_token='.$this->restarter->api_token, ['upload_key' => $key]);
        $response->assertSuccessful();

        $admins = $this->admins;
        $event = $this->event;

        $this->artisan('queue:work --stop-when-empty');

        Notification::assertSentTo(
            $admins,
            AdminModerationEventPhotos::class,
            function ($notification, $channels, $admin) use ($event) {
                // Check that the email was internationalised correctly.
                $mailData = $notification->toMail($admin)->toArray();
                self::assertEquals(__('notifications.greeting', [], $admin->language), $mailData['greeting']);
                self::assertEquals(__('notifications.new_event_photos_subject', [
                    'event' => $event->venue,
                ], $admin->language), $mailData['subject']);

                return true;
            }
        );
        // (Image deletion is covered by APIv2EventImagesTest; this test's
        // concern is only that uploading photos notifies the admins.)
    }

    protected function init_event_and_dependencies()
    {
        /** @var User[] $admins */
        $this->admins = User::factory()->count(5)->administrator()->create();

        // Set some locales.
        $locales = ['en', 'fr'];
        $ix = 0;
        foreach ($this->admins as $admin) {
            $admin->language = $locales[$ix++];
            $ix = $ix % count($locales);
            $admin->save();
        }

        foreach ($this->admins as $admin) {
            $admin->addPreference('admin-moderate-event-photos');
        }

        $this->restarter = User::factory()->restarter()->create();
        $this->group = Group::factory()->create();
        $this->event = Party::factory()->create([
            'group' => $this->group->getKey(),
        ]);

        $this->group->addVolunteer($this->restarter);

        EventsUsers::create([
            'event' => $this->event->getKey(),
            'user' => $this->restarter->getKey(),
            'status' => 1,
            'role' => 4,
            'full_name' => null,
        ]);

        $this->assertTrue($this->group->isVolunteer($this->restarter->getKey()));
        $this->assertTrue($this->event->isVolunteer($this->restarter->getKey()));
    }
}

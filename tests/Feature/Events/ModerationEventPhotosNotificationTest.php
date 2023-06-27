<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Notifications\AdminModerationEvent;
use App\Notifications\AdminModerationEventPhotos;
use App\Party;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ModerationEventPhotosNotificationTest extends TestCase
{
    /**
     * @var User[]
     */
    protected $admins;

    /**
     * @var User
     */
    protected $restarter;

    /**
     * @var Party
     */
    protected $event;

    /**
     * @var Group
     */
    protected $group;

    /** @test */
    public function a_moderation_notification_is_sent_to_admins_when_event_photos_are_uploaded()
    {
        Notification::fake();

        $this->init_event_and_dependencies();

        \Storage::fake('avatars');

        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = TRUE;
        file_put_contents('/tmp/UT.jpg', file_get_contents('public/images/community.jpg'));

        $_FILES = [
            'file' => [
                'error'    => "0",
                'name'     => 'UT.jpg',
                'size'     => 123,
                'tmp_name' => [ '/tmp/UT.jpg' ],
                'type'     => 'image/jpg'
            ]
        ];

        $response = $this->actingAs($this->restarter)
                         ->json('POST', '/party/image-upload/'.$this->event->getKey(), []);
        $response->assertOk();

        $admins = $this->admins;
        $event = $this->event;

        $this->artisan("queue:work --stop-when-empty");

        Notification::assertSentTo(
            $admins,
            AdminModerationEventPhotos::class,
            function ($notification, $channels, $admin) use ($event) {
                // Check that the email was internationalised correctly.
                $mailData = $notification->toMail($admin)->toArray();
                self::assertEquals(__('notifications.greeting', [], $admin->language), $mailData['greeting']);
                self::assertEquals(__('notifications.new_event_photos_subject', [
                    'event' => $event->venue
                ], $admin->language), $mailData['subject']);

                return true;
            }
        );

        // Delete the image.
        $image = \DB::select(DB::raw("SELECT idimages, path FROM images ORDER BY idimages DESC LIMIT 1"));
        $idimages = $image[0]->idimages;
        $path = $image[0]->path;
        $response = $this->get("/party/image/delete/{$event->idevents}/$idimages/$path");
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    protected function init_event_and_dependencies()
    {
        /** @var User[] $admins */
        $this->admins = User::factory()->count(5)->administrator()->create();

        // Set some locales.
        $locales = [
            'en',
            'fr',
        ];

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

        $this->event->increment('volunteers');

        $this->assertTrue($this->group->isVolunteer($this->restarter->getKey()));
        $this->assertTrue($this->event->isVolunteer($this->restarter->getKey()));
    }
}

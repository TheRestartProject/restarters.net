<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\FixometerHelper;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Notifications\AdminModerationEvent;
use App\Notifications\AdminModerationEventPhotos;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

use App\Party;
use App\User;

class ModerateEventPhotosNotificationTest extends TestCase
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

    public function setUp()
    {
        parent::setUp();

        \DB::statement("SET foreign_key_checks=0");

        User::truncate();
        Group::truncate();
        Party::truncate();
        EventsUsers::truncate();
        \DB::table('notifications')->truncate();

        \DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function a_moderation_notification_is_sent_to_admins_when_event_photos_are_uploaded()
    {
        Notification::fake();

        $this->init_event_and_dependencies();

        \Storage::fake('avatars');

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($this->restarter)
                         ->json('POST','/party/image-upload/' . $this->event->getKey(), [
                             'file' => $file
                         ]);
        $response->assertOk();

        Notification::assertSentTo(
            $this->admins, AdminModerationEventPhotos::class
        );
    }

    protected function init_event_and_dependencies()
    {
        /** @var User[] $admins */
        $this->admins = factory(User::class, 5)->states('Administrator')->create();

        foreach ($this->admins as $admin) {
            $admin->addPreference('admin-moderate-event-photos');
        }

        $this->restarter = factory(User::class)->states('Restarter')->create();
        $this->group = factory(Group::class)->create();
        $this->event = factory(Party::class)->create([
            'group' => $this->group->getKey()
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

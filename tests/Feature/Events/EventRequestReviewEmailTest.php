<?php

namespace Tests\Feature;

use App\Party;
use App\User;
use App\Group;
use Tests\TestCase;
use App\EventsUsers;
use App\Notifications\EventRepairs;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;


class EventRequestReviewEmailTest extends TestCase
{
    /**
     * @var User
     */
    protected $admin;

    /**
     * @var User
     */
    protected $volunteer;

    /**
     * @var Group
     */
    protected $group;

    /**
     * @var Party
     */
    protected $event;

    public function setUp()
    {
        parent::setUp();
        \DB::statement("SET foreign_key_checks=0");
        User::truncate();
        Group::truncate();
        Party::truncate();
        EventsUsers::truncate();
        \DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function a_request_review_email_is_sent_to_volunteer()
    {
        Notification::fake();

        $this->init_event_and_dependencies();

        $response = $this
            ->actingAs($this->admin)
            ->get('/party/contribution/' . $this->event->getKey());
        $response->assertRedirect();

        Notification::assertSentTo(
            [$this->volunteer], EventRepairs::class
        );
    }

    protected function init_event_and_dependencies()
    {
        $this->admin = factory(User::class)->states('Administrator')->create();
        $this->group = factory(Group::class)->create();
        $this->volunteer = factory(User::class)->create();
        $this->event = factory(Party::class)->create([
            'group' => $this->group->getKey()
        ]);

        $this->group->addVolunteer($this->volunteer);

        EventsUsers::create([
            'event' => $this->event->getKey(),
            'user' => $this->volunteer->getKey(),
            'status' => 1,
            'role' => 4,
            'full_name' => null,
        ]);

        $this->event->increment('volunteers');

        $this->assertTrue($this->group->isVolunteer($this->volunteer->getKey()));
        $this->assertTrue($this->event->isVolunteer($this->volunteer->getKey()));
    }
}

<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Notifications\EventRepairs;
use App\Party;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

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

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function a_request_review_email_is_sent_to_volunteer()
    {
        Notification::fake();

        $this->init_event_and_dependencies();

        $response = $this
            ->actingAs($this->admin)
            ->get('/party/contribution/'.$this->event->getKey());
        $response->assertRedirect();

        Notification::assertSentTo(
            [$this->volunteer], EventRepairs::class
        );
    }

    protected function init_event_and_dependencies()
    {
        $this->admin = User::factory()->administrator()->create();
        $this->group = Group::factory()->create();
        $this->volunteer = User::factory()->create();
        $this->event = Party::factory()->create([
            'group' => $this->group->getKey(),
        ]);

        $this->group->addVolunteer($this->volunteer);

        EventsUsers::create([
            'event' => $this->event->getKey(),
            'user' => $this->volunteer->getKey(),
            'status' => 1,
            'role' => 4,
            'full_name' => null,
        ]);

        $this->assertTrue($this->group->isVolunteer($this->volunteer->getKey()));
        $this->assertTrue($this->event->isVolunteer($this->volunteer->getKey()));
    }
}

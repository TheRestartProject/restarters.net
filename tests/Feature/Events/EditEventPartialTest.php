<?php

namespace Tests\Feature\Events;

use App\Group;
use App\Party;
use App\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EditEventPartialTest extends TestCase
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private function patchEvent(Party $event, array $data)
    {
        return $this->patch('/api/v2/events/' . $event->idevents, $data);
    }

    private function makeEvent(array $atts = []): Party
    {
        $group = Group::factory()->create();

        return Party::factory()->create(array_merge([
            'group' => $group->idgroups,
            'venue' => 'Library',
            'free_text' => 'Bring your toasters',
            'location' => 'London',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
            'online' => false,
            'link' => 'https://example.org/event',
            'timezone' => 'Europe/London',
            'event_start_utc' => '2100-01-01T10:00:00+00:00',
            'event_end_utc' => '2100-01-01T12:00:00+00:00',
        ], $atts));
    }

    /**
     * @test
     * @story:EventController::updateEventv2
     */
    public function partial_update_only_changes_the_fields_sent(): void
    {
        $event = $this->makeEvent();
        $this->actingAs(User::factory()->administrator()->create());

        $this->patchEvent($event, ['link' => 'https://example.org/new'])->assertSuccessful();

        $event->refresh();
        $this->assertEquals('https://example.org/new', $event->link);
        $this->assertEquals('Library', $event->venue);
        $this->assertEquals('Bring your toasters', $event->free_text);
        $this->assertEquals('London', $event->location);
        $this->assertEquals(51.5073509, $event->latitude);
        $this->assertEquals(-0.1277583, $event->longitude);
        $this->assertEquals('Europe/London', $event->getAttributes()['timezone']);
        $this->assertEquals('2100-01-01 10:00:00', Carbon::parse($event->event_start_utc)->format(self::DATE_FORMAT));
        $this->assertEquals('2100-01-01 12:00:00', Carbon::parse($event->event_end_utc)->format(self::DATE_FORMAT));
    }

    /**
     * @test
     * @story:EventController::updateEventv2
     */
    public function sending_only_the_start_keeps_the_end_and_recalculates_the_hours(): void
    {
        $event = $this->makeEvent();
        $this->actingAs(User::factory()->administrator()->create());

        $this->patchEvent($event, ['start' => '2100-01-01T09:00:00Z'])->assertSuccessful();

        $event->refresh();
        $this->assertEquals('2100-01-01 09:00:00', Carbon::parse($event->event_start_utc)->format(self::DATE_FORMAT));
        $this->assertEquals('2100-01-01 12:00:00', Carbon::parse($event->event_end_utc)->format(self::DATE_FORMAT));
        $this->assertEquals(3, $event->hours);
    }

    /**
     * @test
     * @story:EventController::updateEventv2
     */
    public function sending_a_field_empty_clears_it(): void
    {
        $event = $this->makeEvent(['online' => true]);
        $this->actingAs(User::factory()->administrator()->create());

        $this->patchEvent($event, [
            'link' => '',
            'online' => '0',
        ])->assertSuccessful();

        $event->refresh();
        $this->assertNull($event->link);
        $this->assertFalse((bool) $event->online);
        $this->assertEquals('Library', $event->venue);
    }

    /**
     * @test
     * @story:EventController::updateEventv2
     */
    public function a_new_location_brings_its_map_position(): void
    {
        $event = $this->makeEvent();
        $this->actingAs(User::factory()->administrator()->create());

        $this->patchEvent($event, ['location' => 'Brussels, Belgium'])->assertSuccessful();

        $event->refresh();
        $this->assertEquals('Brussels, Belgium', $event->location);
        $this->assertEquals(50.8503, $event->latitude);
        $this->assertEquals(4.3517, $event->longitude);
        $this->assertEquals('Library', $event->venue);
    }

    /**
     * @test
     * @story:EventController::updateEventv2
     */
    public function a_required_field_sent_empty_is_rejected(): void
    {
        $event = $this->makeEvent();
        $this->actingAs(User::factory()->administrator()->create());

        $this->expectException(ValidationException::class);
        $this->patchEvent($event, ['title' => '']);
    }
}

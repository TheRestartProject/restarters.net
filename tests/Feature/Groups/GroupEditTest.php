<?php

namespace Tests\Feature;

use App\Group;
use App\GroupTags;
use App\Network;
use App\Role;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class GroupEditTest extends TestCase
{
    /**
     * @test
     * @story:GroupController::edit
     * @story:GroupController::updateGroupv2
     */
    public function group_tags_retained_after_edited_by_host(): void
    {
        $this->withoutExceptionHandling();

        $group = Group::factory()->create();
        $tag = GroupTags::factory()->create();
        $group->addTag($tag);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        $response = $this->patch('/api/v2/groups/' . $group->idgroups, [
            'description' => 'Test',
            'location' => 'London',
            'name' => 'Test',
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
            'network_data' => [
                'no_dummy' => 'no_dummy'
            ]
        ]);

        $response->assertSuccessful();

        $group->refresh();
        $this->assertEquals(1, count($group->group_tags));
        $this->assertEquals($tag->tag_name, $group->group_tags[0]->tag_name);

        // Network data should have changed.
        $this->assertEquals($group->network_data, [
            'no_dummy' => 'no_dummy'
        ]);
    }

    /** @story:GroupController::edit */
    public function testEditGroupAsRestarter(): void {
        $group = Group::factory()->create();

        $this->loginAsTestUser(Role::RESTARTER);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->patch('/api/v2/groups/' . $group->idgroups, [
            'description' => 'Test',
            'location' => 'London',
            'name' => 'Test',
        ]);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function invalid_location(): void
    {
        $this->withoutExceptionHandling();

        $group = Group::factory()->create();
        $tag = GroupTags::factory()->create();
        $group->addTag($tag);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        $this->expectException(ValidationException::class);

        $this->patch('/api/v2/groups/' . $group->idgroups, [
            'description' => 'Test',
            'location' => 'zzzzzzzzzzzzz1234',
            'name' => 'Test',
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
        ]);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function partial_update_only_changes_the_fields_sent(): void {
        $group = Group::factory()->create([
            'name' => 'Partial Group',
            'location' => 'Hackney, London',
            'latitude' => 51.545,
            'longitude' => -0.0553,
            'country_code' => 'GB',
            'free_text' => 'About us',
            'website' => 'https://example.org',
            'timezone' => 'Europe/London',
            'email' => 'group@example.org',
            'postcode' => 'E8 1AA',
            'area' => 'London',
            'network_data' => ['foo' => 'bar'],
        ]);
        $event = \App\Party::factory()->create([
            'group' => $group->idgroups,
            'event_start_utc' => Carbon::now()->addWeek()->toIso8601String(),
            'event_end_utc' => Carbon::now()->addWeek()->addHours(2)->toIso8601String(),
            'timezone' => 'Europe/London',
        ]);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        $this->patch('/api/v2/groups/' . $group->idgroups, ['phone' => '999'])->assertSuccessful();

        $group->refresh();
        $this->assertEquals('999', $group->phone);
        $this->assertEquals('Partial Group', $group->name);
        $this->assertEquals('Hackney, London', $group->location);
        $this->assertEquals(51.545, $group->latitude);
        $this->assertEquals(-0.0553, $group->longitude);
        $this->assertEquals('GB', $group->country_code);
        $this->assertEquals('About us', $group->free_text);
        $this->assertEquals('https://example.org', $group->website);
        $this->assertEquals('Europe/London', $group->timezone);
        $this->assertEquals('group@example.org', $group->email);
        $this->assertEquals('E8 1AA', $group->postcode);
        $this->assertEquals('London', $group->area);
        $this->assertEquals(['foo' => 'bar'], $group->network_data);

        // Not sending the timezone isn't a timezone change: future events keep theirs.
        $this->assertEquals('Europe/London', $event->fresh()->timezone);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function archiving_with_only_the_date_keeps_the_rest_of_the_group(): void {
        $group = Group::factory()->create([
            'name' => 'Archive Me',
            'network_data' => ['foo' => 'bar'],
        ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        // What the Archive group action sends.
        $this->patch('/api/v2/groups/' . $group->idgroups, [
            'archived_at' => Carbon::now()->toIso8601String(),
        ])->assertSuccessful();

        $group->refresh();
        $this->assertNotNull($group->archived_at);
        $this->assertEquals('Archive Me', $group->name);
        $this->assertEquals(['foo' => 'bar'], $group->network_data);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function sending_a_field_empty_or_null_clears_it(): void {
        $group = Group::factory()->create([
            'name' => 'Clear Me',
            'website' => 'https://example.org',
            'phone' => '0123',
        ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        // Leaving a field out keeps it; sending it empty (a cleared form field) or null removes it.
        $this->patch('/api/v2/groups/' . $group->idgroups, ['website' => ''])->assertSuccessful();
        $this->patch('/api/v2/groups/' . $group->idgroups, ['phone' => null])->assertSuccessful();

        $group->refresh();
        $this->assertNull($group->website);
        $this->assertNull($group->phone);
        $this->assertEquals('Clear Me', $group->name);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function sending_an_empty_archive_date_unarchives(): void {
        $group = Group::factory()->create([
            'name' => 'Archived',
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->patch('/api/v2/groups/' . $group->idgroups, ['archived_at' => ''])->assertSuccessful();

        $group->refresh();
        $this->assertNull($group->archived_at);
        $this->assertEquals('Archived', $group->name);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function archiving_is_audited_as_a_change_to_the_archive_date_only(): void {
        $group = Group::factory()->create(['name' => 'Audit Me', 'network_data' => ['foo' => 'bar']]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->patch('/api/v2/groups/' . $group->idgroups, [
            'archived_at' => Carbon::now()->toIso8601String(),
        ])->assertSuccessful();

        $audit = DB::table('audits')
            ->where('auditable_type', Group::class)
            ->where('auditable_id', $group->idgroups)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals(['archived_at'], array_keys(json_decode($audit->new_values, true)));
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function changing_the_location_updates_the_map_position_and_country(): void {
        $group = Group::factory()->create([
            'location' => 'London',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
            'country_code' => 'GB',
        ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->patch('/api/v2/groups/' . $group->idgroups, ['location' => 'Brussels, Belgium'])->assertSuccessful();

        $group->refresh();
        $this->assertEquals('Brussels, Belgium', $group->location);
        $this->assertEqualsWithDelta(50.8503, $group->latitude, 0.0001);
        $this->assertEqualsWithDelta(4.3517, $group->longitude, 0.0001);
        $this->assertEquals('BE', $group->country_code);
    }

    private function londonGroupWithFutureEventAsAdmin(): array {
        $group = Group::factory()->create(['timezone' => 'Europe/London']);
        $future = \App\Party::factory()->create([
            'group' => $group->idgroups,
            'event_start_utc' => Carbon::now()->addWeek()->toIso8601String(),
            'event_end_utc' => Carbon::now()->addWeek()->addHours(2)->toIso8601String(),
            'timezone' => 'Europe/London',
        ]);

        $this->actingAs(User::factory()->administrator()->create());

        return [$group, $future];
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function changing_the_timezone_moves_future_events(): void {
        [$group, $future] = $this->londonGroupWithFutureEventAsAdmin();

        $this->patch('/api/v2/groups/' . $group->idgroups, ['timezone' => 'Europe/Brussels'])->assertSuccessful();

        $this->assertEquals('Europe/Brussels', $group->fresh()->timezone);
        $this->assertEquals('Europe/Brussels', $future->fresh()->timezone);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function clearing_the_timezone_leaves_future_events_alone(): void {
        [$group, $future] = $this->londonGroupWithFutureEventAsAdmin();

        $this->patch('/api/v2/groups/' . $group->idgroups, ['timezone' => ''])->assertSuccessful();

        $this->assertEmpty($group->fresh()->getAttributes()['timezone']);
        $this->assertEquals('Europe/London', $future->fresh()->getAttributes()['timezone']);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function host_cannot_archive_or_set_the_area(): void {
        $group = Group::factory()->create(['area' => 'London', 'archived_at' => null]);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        $this->patch('/api/v2/groups/' . $group->idgroups, [
            'area' => 'Elsewhere',
            'archived_at' => Carbon::now()->toIso8601String(),
        ])->assertSuccessful();

        $group->refresh();
        $this->assertEquals('London', $group->area);
        $this->assertNull($group->archived_at);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function approving_without_resending_the_location_still_notifies_nearby_volunteers(): void {
        \Illuminate\Support\Facades\Notification::fake();

        $nearby = User::factory()->create([
            'location' => 'London',
            'latitude' => 51.5072178,
            'longitude' => -0.1275862,
        ]);

        $group = Group::factory()->create([
            'name' => 'Approve Me',
            'location' => 'London',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
            'approved' => false,
        ]);
        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->patch('/api/v2/groups/' . $group->idgroups, ['moderate' => 'approve'])->assertSuccessful();

        \Illuminate\Support\Facades\Notification::assertSentTo([$nearby], \App\Notifications\NewGroupWithinRadius::class);
        $this->assertEquals('Approve Me', $group->fresh()->name);
    }

    /**
     * @test
     * @story:GroupController::updateGroupv2
     */
    public function host_can_edit_postcode_but_not_area(): void {
        $group = Group::factory()->create(['postcode' => 'SW9 7QD', 'area' => 'London']);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        $response = $this->patch('/api/v2/groups/' . $group->idgroups, [
            'postcode' => 'E8 1AA',
            'area' => 'Elsewhere',
        ]);
        $response->assertSuccessful();

        $group->refresh();
        $this->assertEquals('E8 1AA', $group->postcode);
        $this->assertEquals('London', $group->area);

        // A partial update that doesn't send the postcode leaves it alone.
        $response = $this->patch('/api/v2/groups/' . $group->idgroups, [
            'network_data' => ['foo' => 'bar'],
        ]);
        $response->assertSuccessful();
        $this->assertEquals('E8 1AA', $group->refresh()->postcode);

        // Longer than the column.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->patch('/api/v2/groups/' . $group->idgroups, [
            'postcode' => str_repeat('X', 33),
        ]);
    }

    /** @test */
    public function can_edit_timezone(): void {
        // Get list of timezones.
        $response = $this->get('/api/timezones');
        $response->assertSuccessful();
        $timezones = json_decode($response->getContent(), TRUE);
        self::assertGreaterThan(0, count($timezones));
        self::assertTrue(array_key_exists('name', $timezones[0]));

        // Australia/Canberra is an outdated timezone; check it appears.
        $found = false;
        foreach ($timezones as $timezone) {
            if ($timezone['name'] == 'Australia/Canberra') {
                $found = true;
            }
        }

        self::assertTrue($found);
    }

    /**
     * @test
     * @story:GroupController::edit
     * @story:GroupController::updateGroupv2
     */
    public function edit_email(): void
    {
        $this->withoutExceptionHandling();

        $group = Group::factory()->create();

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        $response = $this->patch('/api/v2/groups/' . $group->idgroups, [
            'description' => 'Test',
            'location' => 'London',
            'name' => 'Test',
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
            'network_data' => [
                'no_dummy' => 'no_dummy'
            ],
            'email' => 'info@test.com'
        ]);

        $response->assertSuccessful();

        $group->refresh();
        $this->assertEquals('info@test.com', $group->email);
    }

    /** @story:GroupController::edit */
    public function testEditAsNetworkCoordinator(): void {
        $network = Network::factory()->create();
        $coordinator = User::factory()->restarter()->create();
        $network->addCoordinator($coordinator);
        $coordinator->refresh();
        $this->actingAs($coordinator);

        $idgroups = $this->createGroup(
            'Test Group',
            'https://therestartproject.org',
            'London',
            'Some text.',
            true,
            false,
            'info@test.com'
        );
        $group = Group::find($idgroups);

        // The coordinator is the group's creator, so they're a host of it and can edit it - but
        // shouldn't be able to approve it, as the group has not yet been put in their network (by
        // an admin). GroupPage.vue no longer gets a :can-approve Blade prop; instead
        // GroupController::updateGroupv2 gates the 'moderate' => 'approve' branch on
        // isCoordinatorForGroup(), which is exercised directly here.
        $response = $this->patch('/api/v2/groups/' . $idgroups, [
            'description' => 'Some text.',
            'location' => 'London',
            'name' => $group->name,
            'website' => 'https://therestartproject.org',
            'moderate' => 'approve',
        ]);
        $response->assertSuccessful();

        $group->refresh();
        $this->assertEquals(0, $group->approved); // tinyint 0, not boolean false
    }
}

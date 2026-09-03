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
    /** @test */
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

    /** @test */
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

    /** @test */
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

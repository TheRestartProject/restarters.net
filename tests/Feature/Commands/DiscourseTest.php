<?php

namespace Tests\Commands;

use App\Group;
use App\Network;
use App\User;
use DB;
use Tests\TestCase;

class DiscourseTest extends TestCase {
    public function testSyncDiscourseUsernames() {
        $this->artisan('sync:discourseusernames')->assertExitCode(0);
    }

    public function testDiscourseSyncGroups() {
        $user = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($user);
        $idgroups = $this->createGroup();
        $group = Group::findOrFail($idgroups);

        $this->artisan('network:create testname testshortname "test description" --website="https://therestartproject.org" --language=fr --timezone="Asia/Samarkand" --wordpress --zapier --drip --auto-approve-events')->assertExitCode(0);
        $network = Network::orderBy('id', 'desc')->first();
        $network->discourse_group = 1234;
        $network->save();
        $network->addGroup($group);

        $this->artisan('group:create_discourse_group')->assertExitCode(0);

        // Rename group to trigger the rename of the group on Discourse.

        $group->name = 'New Name';
        $group->save();

        $this->artisan('discourse:syncgroups')->assertExitCode(0);
    }
}
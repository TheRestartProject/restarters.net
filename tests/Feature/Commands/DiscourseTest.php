<?php

namespace Tests\Commands;

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
        $this->artisan('group:create_discourse_group')->assertExitCode(0);
        $this->artisan('discourse:syncgroups')->assertExitCode(0);
    }
}
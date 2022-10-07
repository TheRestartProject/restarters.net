<?php

namespace Tests\Commands;

use App\Group;
use App\Network;
use App\User;
use DB;
use Tests\TestCase;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class NetworkCommandsTest extends TestCase {
    public function testCreate() {
        $this->artisan('network:create testname testshortname "test description" --website="https://therestartproject.org" --language=fr --timezone="Asia/Samarkand" --wordpress --zapier --drip --auto-approve-events')->assertExitCode(0);
        $network = Network::orderBy('id', 'desc')->first();
        $this->assertEquals('testname', $network->name);
        $this->assertEquals('testshortname', $network->shortname);
        $this->assertEquals('test description', $network->description);
        $this->assertEquals('https://therestartproject.org', $network->website);
        $this->assertEquals('fr', $network->default_language);
        $this->assertEquals('Asia/Samarkand', $network->timezone);
        $this->assertEquals(true, $network->events_push_to_wordpress);
        $this->assertEquals(true, $network->include_in_zapier);
        $this->assertEquals(true, $network->users_push_to_drip);
        $this->assertEquals(true, $network->auto_approve_events);
    }

    public function testNetworkCoordinator() {
        $this->artisan('network:create testname testshortname "test description" --website="https://therestartproject.org" --language=fr --timezone="Asia/Samarkand" --wordpress --zapier --drip --auto-approve-events')->assertExitCode(0);
        $network = Network::orderBy('id', 'desc')->first();

        $group = factory(Group::class)->create();
        $network->addGroup($group);
        $user = factory(User::class)->states('Restarter')->create();

        assertFalse($user->isCoordinatorForGroup($group));
        $this->artisan('network:coordinator:add ' . $network->name . ' ' . $user->email)->assertExitCode(0);
        $user->refresh();
        assertTrue($user->isCoordinatorForGroup($group));
    }

    public function testAddGroup() {
        $this->artisan('network:create testname testshortname "test description" --website="https://therestartproject.org" --language=fr --timezone="Asia/Samarkand" --wordpress --zapier --drip --auto-approve-events')->assertExitCode(0);
        $network = Network::orderBy('id', 'desc')->first();

        $group = factory(Group::class)->create();
        $this->artisan('network:group:add "' . $network->name . '" "' . $group->name . '"')->assertExitCode(0);
        $group->refresh();
        $this->assertEquals($network->id, $group->networks->first()->id);
    }
}
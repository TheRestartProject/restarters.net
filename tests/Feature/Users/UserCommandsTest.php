<?php

namespace Tests\Commands;

use App\Group;
use App\Helpers\Fixometer;
use App\Network;
use App\User;
use DB;
use Tests\TestCase;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class UserCommandsTest extends TestCase {
    public function testCreate() {
        $network = Network::all()->first();
        $this->artisan('user:create testname test@test.com 1234567890 fr ' . $network->id)->assertExitCode(0);
        $user = User::where('email', 'test@test.com')->first();
        self::assertEquals('testname', $user->name);
        self::assertEquals('fr', $user->language);
        self::assertEquals($network->id, $user->repair_network);
    }

    public function testMakeHost() {
        $host = User::factory()->host()->create();
        $group = Group::factory()->create();
        assertFalse(Fixometer::userIsHostOfGroup($group->idgroups, $host->id));
        $this->artisan('user:makehost ' . escapeshellarg($host->email) . ' ' . escapeshellarg($group->name))->assertExitCode(0);
        assertTrue(Fixometer::userIsHostOfGroup($group->idgroups, $host->id));
    }
}
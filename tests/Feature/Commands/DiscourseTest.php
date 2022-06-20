<?php

namespace Tests\Commands;

use DB;
use Tests\TestCase;

class DiscourseTest extends TestCase {
    public function testSyncDiscourseUsernames() {
        $this->artisan('sync:discourseusernames')->assertExitCode(0);
    }

    public function testDiscourseSyncGroups() {
        $this->artisan('discourse:syncgroups')->assertExitCode(0);
    }
}
<?php

namespace Tests\Feature\Microtasks\Workbench;

use App\BattcatOra;
use App\Faultcat;
use App\Misccat;
use App\Mobifix;
use App\MobifixOra;
use App\PrintcatOra;
use App\TabicatOra;
use App\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class ContributionsTest extends TestCase
{
    protected $user1;
    protected $user2;

    protected function setUp(): void
    {
        parent::setUp();
        Faultcat::truncate();
        Misccat::truncate();
        Mobifix::truncate();
        MobifixOra::truncate();
        PrintcatOra::truncate();
        TabicatOra::truncate();
        BattcatOra::truncate();

        $this->userWithContributions = User::factory()->restarter()->create();
        $this->anotherUserWithContributions = User::factory()->restarter()->create();
        $this->userNoContributions = User::factory()->restarter()->create();

        // 3 contributions, 3 quests.
        FaultCat::insert([
            'iddevices' => 1,
            'fault_type' => 'Performance',
            'user_id' => $this->userWithContributions->id,
        ]);
        PrintcatOra::insert([
            'id_ords' => 'restart_1',
            'fault_type_id' => 1,
            'user_id' => $this->userWithContributions->id,
        ]);
        TabicatOra::insert([
            'id_ords' => 'restart_1',
            'fault_type_id' => 1,
            'user_id' => $this->userWithContributions->id,
        ]);

        // 2 contributions, 1 quest.
        TabicatOra::insert([
            'id_ords' => 'restart_2',
            'fault_type_id' => 1,
            'user_id' => $this->anotherUserWithContributions->id,
        ]);
        TabicatOra::insert([
            'id_ords' => 'restart_3',
            'fault_type_id' => 1,
            'user_id' => $this->anotherUserWithContributions->id,
        ]);
    }

    public function testLoggedInNoContributions()
    {
        $this->actingAs($this->userNoContributions);
        $response = $this->get('/workbench');

        $this->assertVueProperties($response, [
            [],
            [
                ':current-user-quests' => 0,
                ':current-user-contributions' => 0,
            ],
        ]);
    }

    public function testLoggedInSomeContributions()
    {
        $this->actingAs($this->userWithContributions);
        $response = $this->get('/workbench');

        $this->assertVueProperties($response, [
            [],
            [
                ':current-user-quests' => 3,
                ':current-user-contributions' => 3,
            ],
        ]);
    }

    public function testLoggedInMultipleContributionsOneQuest()
    {
        $this->actingAs($this->anotherUserWithContributions);
        $response = $this->get('/workbench');

        $this->assertVueProperties($response, [
            [],
            [
                ':current-user-quests' => 1,
                ':current-user-contributions' => 2,
            ],
        ]);
    }

    public function testLoggedOut()
    {
        $response = $this->get('/workbench');

        $this->assertVueProperties($response, [
            [
                ':current-user-quests' => 0,
                ':current-user-contributions' => 0,
            ],
        ]);
    }

    public function testTotals()
    {
        $response = $this->get('/workbench');

        $this->assertVueProperties($response, [
            [
                ':total-quests' => 8,
                ':total-contributions' => 5,
            ],
        ]);
    }
}

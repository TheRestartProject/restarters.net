<?php

namespace Tests\Feature\Microtasks\Workbench;

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

    public function setUp()
    {
        parent::setUp();
        Faultcat::truncate();
        Misccat::truncate();
        Mobifix::truncate();
        MobifixOra::truncate();
        PrintcatOra::truncate();
        TabicatOra::truncate();

        $this->userWithContributions = factory(User::class)->state('Restarter')->create();
        $this->anotherUserWithContributions = factory(User::class)->state('Restarter')->create();
        $this->userNoContributions = factory(User::class)->state('Restarter')->create();

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

        $props = $this->getVueProperties($response)[0];
        $this->assertEquals(0, $props[':current-user-quests']);
        $this->assertEquals(0, $props[':current-user-contributions']);
    }

    public function testLoggedInSomeContributions()
    {
        $this->actingAs($this->userWithContributions);
        $response = $this->get('/workbench');

        $props = $this->getVueProperties($response)[0];
        $this->assertEquals(3, $props[':current-user-quests']);
        $this->assertEquals(3, $props[':current-user-contributions']);
    }

    public function testLoggedInMultipleContributionsOneQuest()
    {
        $this->actingAs($this->anotherUserWithContributions);
        $response = $this->get('/workbench');

        $props = $this->getVueProperties($response)[0];
        $this->assertEquals(1, $props[':current-user-quests']);
        $this->assertEquals(2, $props[':current-user-contributions']);
    }

    public function testLoggedOut()
    {
        $response = $this->get('/workbench');

        $props = $this->getVueProperties($response)[0];
        $this->assertEquals(0, $props[':current-user-quests']);
        $this->assertEquals(0, $props[':current-user-contributions']);
    }

    public function testTotals()
    {
        $response = $this->get('/workbench');

        $props = $this->getVueProperties($response)[0];
        $this->assertEquals(6, $props[':total-quests']);
        $this->assertEquals(5, $props[':total-contributions']);
    }
}

<?php

namespace Tests\Unit;

use App\Role;
use App\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DripEventTest extends TestCase
{
    public function testFail(): void {
        $user = User::factory()->create([]);
        $this->expectException(\Exception::class);
        \App\DripEvent::createOrUpdateSubscriber($user, true, $user->email, $user->email);
    }
}

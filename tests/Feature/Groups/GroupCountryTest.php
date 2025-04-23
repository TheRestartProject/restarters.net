<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Notifications\JoinGroup;
use App\Notifications\NewGroupMember;
use App\Helpers\Fixometer;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Models\Party;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Auth;

class GroupCountryTest extends TestCase
{
    public function testSync(): void {
        $this->withoutExceptionHandling();

        $group = Group::factory()->create();
        $group->country_code = 'GB';
        $group->country = null;
        $group->save();

        $this->artisan('groups:country');

        $group = Group::find($group->idgroups);
        $this->assertEquals('United Kingdom', $group->country);
    }
}

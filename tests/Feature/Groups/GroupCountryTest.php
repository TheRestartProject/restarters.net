<?php

namespace Tests\Feature;

use App\Group;
use App\Notifications\JoinGroup;
use App\Notifications\NewGroupMember;
use App\Helpers\Fixometer;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Party;
use App\User;
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

    public function testCountrySetOnSaveWithoutJob(): void {
        // A French-speaking host creating a group must still get the English country name stored.
        app()->setLocale('fr');

        $group = Group::factory()->create(['country_code' => 'BE']);
        $this->assertEquals('Belgium', Group::find($group->idgroups)->country);

        $group->country_code = 'GB';
        $group->save();
        $this->assertEquals('United Kingdom', Group::find($group->idgroups)->country);
    }
}

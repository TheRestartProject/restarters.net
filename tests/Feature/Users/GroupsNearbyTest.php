<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\Group;
use App\GroupTags;
use App\Role;
use App\User;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GroupsNearbyTest extends TestCase
{
    public function testNoLocation() {
        $user = factory(User::class)->create();
        $this->assertEquals(0, count($user->groupsNearby()));
    }

    public function testLocationNoGroups() {
        $user = factory(User::class)->create([
            'latitude' => -12.0464,
            'longitude' => -77.0428
        ]);
        $this->assertEquals(0, count($user->groupsNearby()));
    }

    public function testOneGroupNearby() {
        $groupAttributes = factory(Group::class)->raw();
        $groupAttributes['name'] = 'Lancaster Fixers';
        $group = factory(Group::class)->create([
            'latitude' => -12.0464,
            'longitude' => -77.0428
        ]);
        $user = factory(User::class)->create([
                                                 'latitude' => -12.0463,
                                                 'longitude' => -77.0427
                                             ]);
        $groups = $user->groupsNearby();
        $this->assertEquals(1, count($groups));
        $this->assertEquals($group->id, $groups[0]->id);
    }

    public function testCloseButNoCigar() {
        $group = factory(Group::class)->create([
                                                   'latitude' => -12.0464,
                                                   'longitude' => -77.0428
                                               ]);
        $user = factory(User::class)->create([
                                                 'latitude' => -12.37,
                                                 'longitude' => -77.37
                                             ]);

        // This is about 50.6km away.
        $groups = $user->groupsNearby(10, NULL, User::NEARBY_KM + 1);
        $this->assertEquals(1, count($groups));
        $this->assertEquals($group->id, $groups[0]->id);

        $groups = $user->groupsNearby();
        $this->assertEquals(0, count($groups));
    }

    public function testInactive() {
        $group = factory(Group::class)->create([
                                                   'latitude' => -12.0464,
                                                   'longitude' => -77.0428
                                               ]);

        $inactive = factory(GroupTags::class)->create([
            'id' => GroupTags::INACTIVE,
            'tag_name' => 'Inactive'
        ]);

        $group->addTag($inactive);

        $user = factory(User::class)->create([
                                                 'latitude' => -12.0463,
                                                 'longitude' => -77.0427
                                             ]);
        $groups = $user->groupsNearby();
        $this->assertEquals(0, count($groups));
    }
}

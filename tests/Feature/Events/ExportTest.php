<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use App\Group;
use App\Helpers\RepairNetworkService;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;
use DB;
use Tests\TestCase;

class ExportTest extends TestCase
{
    /**
     * @dataProvider roleProvider
     */
    public function testExport($role)
    {
        $network = Network::factory()->create();

        $admin = User::factory()->administrator()->create();

        switch ($role) {
            case 'Administrator': $user = User::factory()->administrator()->create(); break;
            case 'NetworkCoordinator': $user = User::factory()->networkCoordinator()->create(); break;
            case 'Host': $user = User::factory()->host()->create(); break;
        }

        if ($role == 'NetworkCoordinator') {
            $network->addCoordinator($user);
        }

        $this->actingAs($admin);

        // Create three groups, two approved and one not.
        $group1 = Group::factory()->create([
            'name' => 'test1'
                                                ]);
        $this->networkService = new RepairNetworkService();
        $this->networkService->addGroupToNetwork($admin, $group1, $network);

        if ($role == 'Host') {
            $group1->addVolunteer($user);
            $group1->makeMemberAHost($user);
        }

        $group1->approved = true;
        $group1->save();

        $group2 = Group::factory()->create([
                                                    'name' => 'test2'
                                                ]);
        $this->networkService->addGroupToNetwork($admin, $group2, $network);
        if ($role == 'Host') {
            $group2->addVolunteer($user);
            $group2->makeMemberAHost($user);
        }

        $group2->approved = true;
        $group2->save();

        $group3 = Group::factory()->create([
                                                    'name' => 'test3'
                                                ]);
        $this->networkService->addGroupToNetwork($admin, $group3, $network);
        if ($role == 'Host') {
            $group3->addVolunteer($user);
            $group3->makeMemberAHost($user);
        }

        $group3->approved = false;
        $group3->save();

        $this->actingAs($user);

        // Create an event on each and approve it.
        $idevents1 = $this->createEvent($group1->idgroups, '2000-01-02');
        self::assertNotNull($idevents1);
        $event1 = Party::find($idevents1);
        $event1->approved = true;
        $event1->save();

        $idevents2 = $this->createEvent($group2->idgroups, '2000-01-01');
        self::assertNotNull($idevents2);
        $event2 = Party::find($idevents2);
        $event2->approved = true;
        $event2->save();

        $idevents3 = $this->createEvent($group3->idgroups, '2000-01-01');
        $event3 = Party::find($idevents3);
        $event3->approved = true;
        $event3->save();

        // Add a device for the events.
        $device = Device::factory()->fixed()->create([
                                                                      'category' => 111,
                                                                      'category_creation' => 111,
                                                                      'event' => $idevents1,
                                                                  ]);
        $device = Device::factory()->fixed()->create([
                                                                      'category' => 111,
                                                                      'category_creation' => 111,
                                                                      'event' => $idevents2,
                                                                  ]);
        $device = Device::factory()->fixed()->create([
                                                                      'category' => 111,
                                                                      'category_creation' => 111,
                                                                      'event' => $idevents3,
                                                                  ]);
        // Export parties.
        $response = $this->get("/export/parties?fltr=dummy&parties[0]=$idevents1&parties[1]=$idevents2&parties[2]=$idevents3&from-date=&to-date=");

        // Bit hacky, but grab the file that was created.  Can't find a way to do this in Laravel easily, though it's
        // probably possible using mocking.
        //
        // TODO These files sometimes appear in public/ and sometimes don't.  Is this just an artefact of testing?
        $filename = 'parties.csv';
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals('true', e($row2[3]));
        self::assertEquals($group1->name, $row2[2]);
        $row3 = fgetcsv($fh);
        self::assertEquals('true', e($row3[3]));
        self::assertEquals($group2->name, $row3[2]);

        // Should return the third event as it's for an unapproved group but we're a host.
        $row4 = fgetcsv($fh);
        self::assertEquals('true', e($row4[3]));
        self::assertEquals($group3->name, $row4[2]);

        if ($role == 'Host') {
            // Now remove us as a host of the third group so that it's no longer included in exports.
            $userGroupAssociation = UserGroups::where('user', $user->id)
                ->where('group', $group3->idgroups)->first();
            $userGroupAssociation->role = Role::RESTARTER;
            $userGroupAssociation->save();
        }

        // Export devices.
        $response = $this->get("/export/devices");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);

        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event1->getEventName()), e($row2[7]));
        $row3 = fgetcsv($fh);
        self::assertEquals(e($event2->getEventName()), e($row3[7]));
        $row4 = fgetcsv($fh);

        if ($role == 'Host') {
            self::assertFalse($row4);
        } else {
            self::assertEquals(e($event3->getEventName()), e($row4[7]));
        }

        // Export devices for a particular event.
        $response = $this->get("/export/devices/event/$idevents1");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event1->getEventName()), e($row2[7]));
        $row3 = fgetcsv($fh);

        $response = $this->get("/export/devices/event/$idevents2");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event2->getEventName()), e($row2[7]));

        $response = $this->get("/export/devices/event/$idevents3");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);

        if ($role == 'Host') {
            self::assertFalse($row2);
        } else {
            self::assertEquals(e($event3->getEventName()), e($row2[7]));
        }

        // Export devices for a particular group.
        $response = $this->get("/export/devices/group/{$group1->idgroups}");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event1->getEventName()), e($row2[7]));

        $response = $this->get("/export/devices/group/{$group2->idgroups}");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event2->getEventName()), e($row2[7]));

        $response = $this->get("/export/devices/group/{$group3->idgroups}");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);

        if ($role == 'Host') {
            self::assertFalse($row2);
        } else {
            self::assertEquals(e($event3->getEventName()), e($row2[7]));
        }

        // Export time volunteered - first as a web page.
        $response = $this->get("/reporting/time-volunteered?a");
        $response->assertSee($event1->getEventName());
        $response->assertSee($event2->getEventName());

        // Now as a CSV.
        $response = $this->get("/export/time-volunteered?a");
        $filename = 'time_reporting.csv';
        $fh = fopen($filename, 'r');
        $row1 = fgetcsv($fh);
        $row2 = fgetcsv($fh);
        $this->assertEquals('Hours Volunteered', $row2[0]);
    }

    public function roleProvider() {
        return [
            [ 'Administrator' ],
            [ 'NetworkCoordinator' ],
            [ 'Host' ],
        ];
    }
}

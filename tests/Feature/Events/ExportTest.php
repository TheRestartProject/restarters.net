<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use App\Group;
use App\Helpers\RepairNetworkService;
use App\Network;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;

class ExportTest extends TestCase
{
    public function testExport()
    {
        $network = factory(Network::class)->create();

        $host = factory(User::class)->states('Administrator')->create();
        $this->actingAs($host);

        // Create two groups.
        $group1 = factory(Group::class)->create([
            'name' => 'test1'
                                                ]);
        $this->networkService = new RepairNetworkService();
        $this->networkService->addGroupToNetwork($host, $group1, $network);
        $group1->addVolunteer($host);
        $group1->makeMemberAHost($host);

        $group2 = factory(Group::class)->create([
                                                    'name' => 'test2'
                                                ]);
        $this->networkService->addGroupToNetwork($host, $group2, $network);
        $group2->addVolunteer($host);
        $group2->makeMemberAHost($host);

        // Create an event on each and approve it.
        $idevents1 = $this->createEvent($group1->idgroups, '2000-01-02');
        $event1 = Party::find($idevents1);
        $event1->approved = true;
        $event1->save();

        $idevents2 = $this->createEvent($group2->idgroups, '2000-01-01');
        $event2 = Party::find($idevents2);
        $event2->approved = true;
        $event2->save();

        // Add a device for the first event.
        $device = factory(Device::class)->states('fixed')->create([
                                                                      'category' => 111,
                                                                      'category_creation' => 111,
                                                                      'event' => $idevents1,
                                                                  ]);
        $device = factory(Device::class)->states('fixed')->create([
                                                                      'category' => 111,
                                                                      'category_creation' => 111,
                                                                      'event' => $idevents2,
                                                                  ]);
        // Export parties.
        $response = $this->get("/export/parties?fltr=dummy&parties[0]=$idevents1&parties[1]=$idevents2&from-date=&to-date=");

        // Bit hacky, but grab the file that was created.  Can't find a way to do this in Laravel easily, though it's
        // probably possible using mocking.
        //
        // TODO These files sometimes appear in public/ and sometimes don't.  Is this just an artefact of testing?
        $filename = 'parties.csv';
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals($group1->name, $row2[2]);
        $row3 = fgetcsv($fh);
        self::assertEquals($group2->name, $row3[2]);

        // Export devices.
        $response = $this->get("/export/devices");

        $filename = 'public/devices.csv';
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event1->getEventName()), e($row2[6]));

        // Export time volunteered - first as a web page.
        $response = $this->get("/reporting/time-volunteered?a");
        $response->assertSee(e($event1->getEventName()));
        $response->assertSee(e($event2->getEventName()));

        // Now as a CSV.
        $response = $this->get("/export/time-volunteered?a");
        $filename = 'time_reporting.csv';
        $fh = fopen($filename, 'r');
        $row1 = fgetcsv($fh);
        $row2 = fgetcsv($fh);
        $this->assertEquals('Hours Volunteered', $row2[0]);
    }
}

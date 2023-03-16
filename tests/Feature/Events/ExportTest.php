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
        $network = Network::factory()->create();

        $host = User::factory()->administrator()->create();
        $this->actingAs($host);

        // Create two groups.
        $group1 = Group::factory()->create([
            'name' => 'test1'
                                                ]);
        $this->networkService = new RepairNetworkService();
        $this->networkService->addGroupToNetwork($host, $group1, $network);
        $group1->addVolunteer($host);
        $group1->makeMemberAHost($host);

        $group2 = Group::factory()->create([
                                                    'name' => 'test2'
                                                ]);
        $this->networkService->addGroupToNetwork($host, $group2, $network);
        $group2->addVolunteer($host);
        $group2->makeMemberAHost($host);

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
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);

        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event1->getEventName()), e($row2[7]));
        $row3 = fgetcsv($fh);
        self::assertEquals(e($event2->getEventName()), e($row3[7]));

        // Export devices for a particular event.
        $response = $this->get("/export/devices/event/$idevents1");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event1->getEventName()), e($row2[7]));
        $row3 = fgetcsv($fh);
        self::assertFalse($row3);

        $response = $this->get("/export/devices/event/$idevents2");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event2->getEventName()), e($row2[7]));
        $row3 = fgetcsv($fh);
        self::assertFalse($row3);

        // Export devices for a particular group.
        $response = $this->get("/export/devices/group/{$group1->idgroups}");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event1->getEventName()), e($row2[7]));
        $row3 = fgetcsv($fh);
        self::assertFalse($row3);

        $response = $this->get("/export/devices/group/{$group2->idgroups}");
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(e($event2->getEventName()), e($row2[7]));
        $row3 = fgetcsv($fh);
        self::assertFalse($row3);

        // Export devices as though we are therestartproject.org, which for some reason doesn't contain the model
        // column.
        $response = $this->get("/export/devices/event/$idevents1", ['HTTP_REFERER' => 'http://therestartproject.org']);
        $header = $response->headers->get('content-disposition');
        $filename = public_path() . '/' . substr($header, strpos($header, 'filename=') + 9);
        $fh = fopen($filename, 'r');
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        self::assertEquals(11, count($row2));

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
}

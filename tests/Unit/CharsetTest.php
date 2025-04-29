<?php

namespace Tests\Unit;

use App\Models\Device;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Group;
use App\Models\Network;
use App\Models\Party;
use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CharsetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function test_charset_db_insert(): void
    {
        DB::statement('SET foreign_key_checks=0');

        $bom = "\xEF\xBB\xBF";

        // User
        $name = 'Möñiqûé';
        $shortname = 'Möñ';
        Log::info("$bom User name: $name");
        Log::info("$bom User shortname: $shortname");
        $id = DB::table('users')->insertGetId(
                [
                    'name' => $name,
                    'username' => $shortname,
                ]
        );
        $user = DB::table('users')->where('id', $id)->first();
        Log::info("$bom user->name: ".$user->name);
        $this->assertEquals($id, $user->id);
        $this->assertEquals($name, $user->name);
        $this->assertEquals($shortname, $user->username);

        // Group
        $name = "Repair Café -l'Möñiqûé";
        $location = 'Malmö, Sweden';
        $freetext = "Le Repair Café est ouvert à tous et l'entrée est évidemment libre.";
        Log::info("$bom Group name: $name");
        Log::info("$bom Group location: $location");
        Log::info("$bom Group freetext: $freetext");
        $id = DB::table('groups')->insertGetId(
                [
                    'name' => $name,
                    'location' => $location,
                    'free_text' => $freetext,
                ]
        );
        $group = DB::table('groups')->where('idgroups', $id)->first();
        Log::info("$bom group->name: ".$group->name);
        $this->assertEquals($id, $group->idgroups);
        $this->assertEquals($name, $group->name);
        $this->assertEquals($location, $group->location);
        $this->assertEquals($freetext, $group->free_text);

        // User Group
        DB::table('users_groups')->insert(
                [
                    'user' => $user->id,
                    'group' => $group->idgroups,
                    'status' => 1,
                ]
        );

        // Event
        $venue = "Repair Café -l'Möñiqûé";
        $location = 'Stapelbäddsgatan, Malmö, Sweden';
        $freetext = 'Petit électro, électronique, vélo ...';
        Log::info("$bom Event venue: $venue");
        Log::info("$bom Event location: $location");
        Log::info("$bom Event freetext: $freetext");
        $id = DB::table('events')->insertGetId(
                [
                    'venue' => $venue,
                    'location' => $location,
                    'free_text' => $freetext,
                    'group' => $group->idgroups,
                    'volunteers' => 1,
                ]
        );
        $event = DB::table('events')->where('idevents', $id)->first();
        Log::info("$bom event->venue: ".$event->venue);
        $this->assertEquals($id, $event->idevents);
        $this->assertEquals($venue, $event->venue);
        $this->assertEquals($location, $event->location);
        $this->assertEquals($freetext, $event->free_text);

        // Event User
        DB::table('events_users')->insert(
                [
                    'user' => $user->id,
                    'event' => $event->idevents,
                    'status' => 1,
                ]
        );

        // Network
        $name = "Repair Réseau - l'Möñiqûé";
        $shortname = 'Réseau Möñiqûé';
        $description = "Le Repair Réseau de l'Möñiqûé";
        Log::info("$bom Network name: $name");
        Log::info("$bom Network shortname: $shortname");
        Log::info("$bom Network description: $description");
        $id = DB::table('networks')->insertGetId(
                [
                    'name' => $name,
                    'shortname' => $shortname,
                    'description' => $description,
                ]
        );
        $network = DB::table('networks')->where('id', $id)->first();
        Log::info("$bom network->name: ".$network->name);
        $this->assertEquals($id, $network->id);
        $this->assertEquals($name, $network->name);
        $this->assertEquals($shortname, $network->shortname);
        $this->assertEquals($description, $network->description);

        // Device
        $brand = 'Mû';
        $model = 'hÖv€rçråft';
        $problem = 'is ßûll ôf Éèls.';
        $str = '£öÖ ßàñ';
        Log::info("$bom Device problem: $problem");
        Log::info("$bom Device model: $model");
        Log::info("$bom Device brand: $brand");
        Log::info("$bom Device: $str");
        $id = DB::table('devices')->insertGetId(
                [
                    'brand' => $brand,
                    'model' => $model,
                    'problem' => $problem,
                    'notes' => $str,
                    'item_type' => $str,
                    'category' => 46,
                    'event' => $event->idevents,
                ]
        );
        $device = DB::table('devices')->where('iddevices', $id)->first();
        Log::info("$bom device->brand: ".$device->brand);
        $this->assertEquals($id, $device->iddevices);
        $this->assertEquals($brand, $device->brand);
        $this->assertEquals($model, $device->model);
        $this->assertEquals($problem, $device->problem);
        $this->assertEquals($str, $device->item_type);
        $this->assertEquals($str, $device->notes);

        // Visit the event page
        $response = $this->get('party/view/'.$event->idevents);
        $response->assertSeeText($group->name);
        $response->assertSeeText($event->venue);
        $response->assertSeeText($device->brand);
        $response->assertSeeText($device->model);
        $response->assertSeeText($device->problem);
        // file_put_contents(storage_path().'/logs/DAT21-event.html', $response->content());

        DB::statement('SET foreign_key_checks=1');
    }
}

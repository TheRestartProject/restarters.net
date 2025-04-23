<?php

namespace Tests\Commands;

use App\Models\Group;
use App\Helpers\Fixometer;
use App\Models\Network;
use App\Models\User;
use DB;
use Tests\TestCase;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class GroupCommandsTest extends TestCase {
    public function testImport(): void {
        $network = Network::all()->first();
        file_put_contents('/tmp/group.csv', "Headers\nTest,London,SW9 7QD,London,UK,51.5073510,-0.1277584,https://therestartproject.org,1234,,Testing\n");
        $this->artisan('import:groups /tmp/group.csv');
        $group = Group::where('name', 'Test')->first();
        self::assertNotNull($group);
        self::assertEquals('Test', $group->name);
        self::assertEquals('London', $group->location);
        self::assertEquals('SW9 7QD', $group->postcode);
        self::assertEquals('UK', $group->country_code);
        self::assertEquals('51.5073510', $group->latitude);
        self::assertEquals('-0.1277584', $group->longitude);
        self::assertEquals('https://therestartproject.org', $group->website);
        self::assertEquals('1234', $group->phone);
        self::assertEquals('Testing', $group->free_text);
    }
}
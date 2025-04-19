<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NetworksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Restarters network is REQUIRED, so is created as part of the database
        // migrations with id = 1.

        DB::table('networks')->insert([
            'id' => 2,
            'name' => 'Repair & Share',
            'website' => 'https://repairshare.be',
            'default_language' => 'nl-BE',
            'timezone' => 'Europe/Brussels',
            'events_push_to_wordpress' => false,
            'include_in_zapier' => false,
            'users_push_to_drip' => false,
            'shortname' => 'repairshare',
            'created_at' => Carbon::now(),
        ]);

        DB::table('networks')->insert([
            'id' => 3,
            'name' => 'Repair Together',
            'website' => 'https://repairtogether.be',
            'default_language' => 'fr',
            'timezone' => 'Europe/Brussels',
            'events_push_to_wordpress' => false,
            'include_in_zapier' => false,
            'users_push_to_drip' => false,
            'shortname' => 'repairtogether',
            'created_at' => Carbon::now(),
        ]);
    }
}
